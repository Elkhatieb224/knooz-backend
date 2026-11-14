<?php

namespace App\Services\Company;

use App\Enums\CompanyUserRole;
use App\Enums\Theme;
use App\Enums\UserStatus;
use App\Models\CompanyUser;
use App\Models\PanelApiToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanyStaffService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $companyId, array $data, ?CompanyUser $actor = null): CompanyUser
    {
        $this->assertActorMayAssignRole($actor, $data['role'] ?? null);

        return CompanyUser::query()->create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => $data['role'] ?? CompanyUserRole::Viewer->value,
            'status' => $data['status'] ?? UserStatus::Active->value,
            'theme' => Theme::Light,
            'created_by' => $actor?->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CompanyUser $staff, array $data, ?CompanyUser $actor = null): CompanyUser
    {
        $this->assertActorMayManage($actor, $staff);

        $payload = collect($data)->only(['name', 'email', 'phone'])->all();

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        if (isset($data['role'])) {
            $newRole = $data['role'] instanceof CompanyUserRole
                ? $data['role']
                : CompanyUserRole::from($data['role']);

            if ($newRole !== $staff->role) {
                if ($actor && $actor->is($staff)) {
                    throw ValidationException::withMessages([
                        'role' => ['لا يمكنك تغيير دورك بنفسك.'],
                    ]);
                }

                if ($staff->isOwner()) {
                    $this->assertNotLastActiveOwner($staff, 'لا يمكن تغيير دور المالك الأخير النشط.', 'role');
                }

                $this->assertActorMayAssignRole($actor, $newRole);

                $payload['role'] = $newRole->value;
            }
        }

        $staff->update($payload);

        return $staff->refresh();
    }

    public function toggleStatus(CompanyUser $staff, ?CompanyUser $actor = null): CompanyUser
    {
        $this->assertActorMayManage($actor, $staff);

        if ($actor && $actor->is($staff)) {
            throw ValidationException::withMessages([
                'status' => ['لا يمكنك تغيير حالة حسابك بنفسك.'],
            ]);
        }

        if ($staff->status === UserStatus::Active) {
            if ($staff->isOwner()) {
                $this->assertNotLastActiveOwner($staff, 'لا يمكن تعطيل المالك الأخير النشط.', 'status');
            }

            DB::transaction(function () use ($staff) {
                $staff->update(['status' => UserStatus::Inactive->value]);
                $this->revokeTokens($staff);
            });
        } else {
            $staff->update(['status' => UserStatus::Active->value]);
        }

        return $staff->refresh();
    }

    public function revokeTokens(CompanyUser $staff): void
    {
        PanelApiToken::query()
            ->where('tokenable_type', $staff->getMorphClass())
            ->where('tokenable_id', $staff->getKey())
            ->delete();
    }

    public function delete(CompanyUser $staff, ?CompanyUser $actor = null): void
    {
        $this->assertActorMayManage($actor, $staff);

        if ($actor && $actor->is($staff)) {
            throw ValidationException::withMessages([
                'staff' => ['لا يمكنك حذف حسابك بنفسك.'],
            ]);
        }

        if ($staff->isOwner()) {
            $this->assertNotLastActiveOwner($staff, 'لا يمكن حذف المالك الأخير النشط.', 'staff');
        }

        DB::transaction(function () use ($staff) {
            $this->revokeTokens($staff);
            $staff->delete();
        });
    }

    /**
     * Only owners may manage owner accounts or grant the owner role.
     */
    protected function assertActorMayManage(?CompanyUser $actor, CompanyUser $staff): void
    {
        if ($actor && ! $actor->isOwner() && $staff->isOwner()) {
            abort(403, 'لا يمكنك إدارة حساب مالك.');
        }
    }

    protected function assertActorMayAssignRole(?CompanyUser $actor, CompanyUserRole|string|null $role): void
    {
        $role = $role instanceof CompanyUserRole ? $role : ($role ? CompanyUserRole::tryFrom($role) : null);

        if ($actor && ! $actor->isOwner() && $role === CompanyUserRole::Owner) {
            abort(403, 'فقط المالك يمكنه منح دور المالك.');
        }
    }

    protected function assertNotLastActiveOwner(CompanyUser $staff, string $message, string $field): void
    {
        if ($staff->status !== UserStatus::Active) {
            return;
        }

        $others = CompanyUser::query()
            ->where('company_id', $staff->company_id)
            ->where('role', CompanyUserRole::Owner->value)
            ->where('status', UserStatus::Active->value)
            ->whereKeyNot($staff->getKey())
            ->count();

        if ($others === 0) {
            throw ValidationException::withMessages([$field => [$message]]);
        }
    }
}

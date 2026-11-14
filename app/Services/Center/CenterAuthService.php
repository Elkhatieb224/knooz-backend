<?php

namespace App\Services\Center;

use App\Enums\CenterStatus;
use App\Enums\CenterUserRole;
use App\Enums\UserStatus;
use App\Models\CenterUser;
use App\Models\PanelApiToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class CenterAuthService
{
    /**
     * @return array{user: CenterUser, token: string}
     */
    public function login(string $login, string $password): array
    {
        $user = CenterUser::query()
            ->with('center')
            ->where(function ($q) use ($login) {
                $q->where('email', $login)->orWhere('phone', $login);
            })
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['بيانات الدخول غير صحيحة.'],
            ]);
        }

        if ($user->status !== UserStatus::Active) {
            throw ValidationException::withMessages([
                'login' => ['حسابك غير نشط. تواصل مع الإدارة.'],
            ]);
        }

        if (! $user->center_id || ! $user->center) {
            throw ValidationException::withMessages([
                'login' => ['حساب المركز غير مرتبط بمركز خدمة.'],
            ]);
        }

        if ($user->center->status !== CenterStatus::Active) {
            throw ValidationException::withMessages([
                'login' => ['المركز غير نشط حالياً.'],
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $issued = PanelApiToken::issue($user, 'center', 'center-app');

        return ['user' => $user->fresh('center'), 'token' => $issued['plain']];
    }

    public function logout(?string $plainToken): void
    {
        if ($plainToken) {
            PanelApiToken::revokePlain($plainToken);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(CenterUser $user, array $data): CenterUser
    {
        $payload = collect($data)->only([
            'name', 'email', 'phone', 'theme', 'avatar',
        ])->filter(fn ($v) => $v !== null)->all();

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->fill($payload);
        $user->save();

        return $user->fresh('center');
    }

    public function sendResetLink(string $email): string
    {
        return Password::broker('center_users')->sendResetLink(['email' => $email]);
    }

    /**
     * @param  array{email: string, token: string, password: string, password_confirmation: string}  $data
     */
    public function resetPassword(array $data): string
    {
        return Password::broker('center_users')->reset(
            [
                'email' => $data['email'],
                'token' => $data['token'],
                'password' => $data['password'],
                'password_confirmation' => $data['password_confirmation'],
            ],
            function (CenterUser $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                ])->save();

                PanelApiToken::query()
                    ->where('tokenable_type', $user->getMorphClass())
                    ->where('tokenable_id', $user->getKey())
                    ->delete();
            }
        );
    }

    public function ensureRole(CenterUser $user): CenterUserRole
    {
        return $user->role ?? CenterUserRole::Manager;
    }
}

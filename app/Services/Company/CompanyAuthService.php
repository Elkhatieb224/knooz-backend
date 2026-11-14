<?php

namespace App\Services\Company;

use App\Enums\CompanyStatus;
use App\Enums\UserStatus;
use App\Models\CompanyUser;
use App\Models\PanelApiToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class CompanyAuthService
{
    /**
     * @return array{user: CompanyUser, token: string}
     */
    public function login(string $login, string $password): array
    {
        $user = CompanyUser::query()
            ->with('company')
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

        if (! $user->company_id || ! $user->company) {
            throw ValidationException::withMessages([
                'login' => ['حساب الشركة غير مرتبط بشركة.'],
            ]);
        }

        if ($user->company->status !== CompanyStatus::Active) {
            throw ValidationException::withMessages([
                'login' => ['الشركة غير نشطة حالياً.'],
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $issued = PanelApiToken::issue($user, 'company', 'company-app');

        return ['user' => $user->fresh('company'), 'token' => $issued['plain']];
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
    public function updateProfile(CompanyUser $user, array $data): CompanyUser
    {
        $payload = collect($data)->only([
            'name', 'email', 'phone', 'theme', 'avatar',
        ])->filter(fn ($v) => $v !== null)->all();

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->fill($payload);
        $user->save();

        return $user->fresh('company');
    }

    public function sendResetLink(string $email): string
    {
        return Password::broker('company_users')->sendResetLink(['email' => $email]);
    }

    /**
     * @param  array{email: string, token: string, password: string, password_confirmation: string}  $data
     */
    public function resetPassword(array $data): string
    {
        return Password::broker('company_users')->reset(
            [
                'email' => $data['email'],
                'token' => $data['token'],
                'password' => $data['password'],
                'password_confirmation' => $data['password_confirmation'],
            ],
            function (CompanyUser $user, string $password) {
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
}

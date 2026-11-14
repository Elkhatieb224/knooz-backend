<?php

namespace App\Services\Api;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\Wallet;
use App\Support\CustomerRoles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerAuthService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'gender' => $data['gender'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'city' => $data['city'] ?? null,
                'password' => $data['password'],
                'role' => UserRole::CustomerIndividual,
                'status' => UserStatus::Active,
                'qr_code' => 'USR-'.Str::upper(Str::random(10)),
                'nfc_code' => 'NFC-'.Str::upper(Str::random(10)),
                'loyalty_points' => 0,
                'notifications_enabled' => true,
            ]);

            Wallet::query()->create([
                'user_id' => $user->id,
                'balance' => 0,
            ]);

            $token = $user->createToken('mobile')->plainTextToken;

            return ['user' => $user->fresh('wallet'), 'token' => $token];
        });
    }

    /**
     * @return array{user: User, token: string}
     */
    public function login(string $login, string $password): array
    {
        $user = User::query()
            ->where(function ($q) use ($login) {
                $q->where('email', $login)->orWhere('phone', $login);
            })
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['بيانات الدخول غير صحيحة.'],
            ]);
        }

        if (! CustomerRoles::isCustomer($user)) {
            throw ValidationException::withMessages([
                'login' => ['هذا الحساب غير مخصص لتطبيق العملاء.'],
            ]);
        }

        if ($user->status !== UserStatus::Active) {
            throw ValidationException::withMessages([
                'login' => ['الحساب غير نشط. تواصل مع الدعم.'],
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $token = $user->createToken('mobile')->plainTextToken;

        return ['user' => $user->load('wallet'), 'token' => $token];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->fill(collect($data)->only([
            'name', 'email', 'phone', 'city', 'gender', 'theme', 'notifications_enabled',
        ])->filter(fn ($v) => $v !== null)->all());

        $user->save();

        return $user->fresh('wallet');
    }

    public function changePassword(User $user, string $current, string $new): void
    {
        if (! Hash::check($current, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['كلمة المرور الحالية غير صحيحة.'],
            ]);
        }

        $user->update(['password' => $new]);
        $user->tokens()->delete();
    }
}

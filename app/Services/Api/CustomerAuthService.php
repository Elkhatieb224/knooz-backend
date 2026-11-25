<?php

namespace App\Services\Api;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\Wallet;
use App\Support\CustomerRoles;
use Illuminate\Support\Facades\Cache;
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
            $user = User::query()->forceCreate([
                'name' => $data['name'],
                'gender' => $data['gender'] ?? null,
                'email' => $data['email'],
                'phone' => $this->normalizePhone($data['phone']),
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
        $user = $this->findCustomerByLogin($login);

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
        if (array_key_exists('phone', $data) && $data['phone'] !== null) {
            $data['phone'] = $this->normalizePhone((string) $data['phone']);
        }

        $user->fill(collect($data)->only([
            'name', 'email', 'phone', 'city', 'gender', 'theme', 'notifications_enabled',
        ])->filter(fn ($v) => $v !== null)->all());

        $user->save();

        return $user->fresh('wallet');
    }

    public function updateAvatar(User $user, \Illuminate\Http\UploadedFile $file): User
    {
        $path = $file->store('avatars/'.$user->id, 'public');

        if (filled($user->avatar)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
        }

        $user->forceFill(['avatar' => $path])->save();

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

    /**
     * OTP stub: any non-empty code is accepted until an SMS provider is wired.
     *
     * @return array{verified: bool, reset_token: string, expires_in: int}
     */
    public function verifyOtpStub(string $login, string $otp): array
    {
        if (trim($otp) === '') {
            throw ValidationException::withMessages([
                'otp' => ['أدخل رمز التحقق.'],
            ]);
        }

        // Do not reveal whether the account exists.
        $resetToken = Str::random(64);
        Cache::put($this->resetCacheKey($login), $resetToken, now()->addMinutes(15));

        return [
            'verified' => true,
            'reset_token' => $resetToken,
            'expires_in' => 900,
        ];
    }

    public function resetPasswordWithToken(string $login, string $resetToken, string $password): void
    {
        $expected = Cache::get($this->resetCacheKey($login));
        if (! is_string($expected) || ! hash_equals($expected, $resetToken)) {
            throw ValidationException::withMessages([
                'reset_token' => ['انتهت صلاحية رمز الاستعادة أو أنه غير صالح.'],
            ]);
        }

        $user = $this->findCustomerByLogin($login);
        if (! $user || ! CustomerRoles::isCustomer($user)) {
            // Same generic message — do not leak account existence.
            throw ValidationException::withMessages([
                'login' => ['تعذر تحديث كلمة المرور.'],
            ]);
        }

        $user->update(['password' => $password]);
        $user->tokens()->delete();
        Cache::forget($this->resetCacheKey($login));
    }

    public function findCustomerByLogin(string $login): ?User
    {
        $candidates = $this->phoneCandidates($login);

        return User::query()
            ->where(function ($q) use ($login, $candidates) {
                $q->where('email', $login);
                foreach ($candidates as $candidate) {
                    $q->orWhere('phone', $candidate);
                }
            })
            ->first();
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '966') && strlen($digits) >= 12) {
            return '0'.substr($digits, 3);
        }

        if (strlen($digits) === 9 && str_starts_with($digits, '5')) {
            return '0'.$digits;
        }

        return $digits !== '' ? $digits : trim($phone);
    }

    /**
     * @return list<string>
     */
    protected function phoneCandidates(string $login): array
    {
        $raw = trim($login);
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        $out = array_filter([$raw, $digits]);

        if ($digits === '') {
            return array_values(array_unique($out));
        }

        if (str_starts_with($digits, '966') && strlen($digits) >= 12) {
            $local = substr($digits, 3);
            $out[] = $local;
            $out[] = '0'.$local;
        }

        if (str_starts_with($digits, '0') && strlen($digits) >= 10) {
            $local = substr($digits, 1);
            $out[] = $local;
            $out[] = '966'.$local;
        }

        if (strlen($digits) === 9 && str_starts_with($digits, '5')) {
            $out[] = '0'.$digits;
            $out[] = '966'.$digits;
        }

        return array_values(array_unique($out));
    }

    protected function resetCacheKey(string $login): string
    {
        return 'customer_pwd_reset:'.Str::lower(trim($login));
    }
}

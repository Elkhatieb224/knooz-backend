<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\Api\CustomerAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(protected CustomerAuthService $auth)
    {
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'gender' => ['nullable', 'string', 'max:16'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:120'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $data['phone'] = $this->auth->normalizePhone($data['phone']);
        if ($this->auth->findCustomerByLogin($data['phone'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'phone' => ['رقم الجوال مستخدم مسبقاً.'],
            ]);
        }

        $result = $this->auth->register($data);

        return ApiResponse::success([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'user' => new UserResource($result['user']),
        ], 'تم إنشاء الحساب بنجاح.', 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->auth->login($data['login'], $data['password']);

        return ApiResponse::success([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'user' => new UserResource($result['user']),
        ], 'تم تسجيل الدخول.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return ApiResponse::success(null, 'تم تسجيل الخروج.');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()->load('wallet')));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'gender' => ['nullable', 'string', 'max:16'],
            'email' => ['sometimes', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['sometimes', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
            'city' => ['nullable', 'string', 'max:120'],
            'theme' => ['nullable', 'in:light,dark'],
            'notifications_enabled' => ['nullable', 'boolean'],
        ]);

        $user = $this->auth->updateProfile($user, $data);

        return ApiResponse::success(new UserResource($user), 'تم تحديث الملف الشخصي.');
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'avatar' => ['required', 'image', 'max:4096'],
        ]);

        $user = $this->auth->updateAvatar($request->user(), $data['avatar']);

        return ApiResponse::success(new UserResource($user), 'تم تحديث صورة الملف الشخصي.');
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $this->auth->changePassword($request->user(), $data['current_password'], $data['password']);

        return ApiResponse::success(null, 'تم تغيير كلمة المرور. سجّل الدخول مجدداً.');
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'login' => ['required', 'string'],
        ]);

        // OTP provider integration point — do not leak whether account exists.
        // FCM/SMS are not required for this stub path.
        return ApiResponse::success([
            'sent' => true,
            'channel' => 'sms',
            'expires_in' => 300,
            'dev_accepts_any_otp' => true,
        ], 'إن وُجد الحساب سيتم إرسال رمز التحقق.');
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'otp' => ['required', 'string', 'max:32'],
        ]);

        $result = $this->auth->verifyOtpStub($data['login'], $data['otp']);

        return ApiResponse::success($result, 'تم التحقق من الرمز.');
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'reset_token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $this->auth->resetPasswordWithToken(
            $data['login'],
            $data['reset_token'],
            $data['password'],
        );

        return ApiResponse::success(null, 'تم تحديث كلمة المرور. سجّل الدخول مجدداً.');
    }
}

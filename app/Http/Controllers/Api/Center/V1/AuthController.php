<?php

namespace App\Http\Controllers\Api\Center\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Center\LoginRequest;
use App\Http\Requests\Api\Center\UpdateProfileRequest;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Http\Resources\Center\CenterUserResource;
use App\Services\Center\CenterAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(protected CenterAuthService $auth)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login(
            $request->validated('login'),
            $request->validated('password'),
        );

        return ApiResponse::success([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'user' => new CenterUserResource($result['user']),
        ], 'تم تسجيل الدخول.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->attributes->get('panel_api_token_plain'));

        return ApiResponse::success(null, 'تم تسجيل الخروج.');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(
            new CenterUserResource($request->user()->load('center'))
        );
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->auth->updateProfile($request->user(), $request->validated());

        return ApiResponse::success(
            new CenterUserResource($user),
            'تم تحديث الملف الشخصي.'
        );
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = $this->auth->sendResetLink($data['email']);

        // Do not leak whether the account exists.
        return ApiResponse::success([
            'sent' => true,
            'channel' => 'email',
            'status' => $status === Password::RESET_LINK_SENT,
        ], 'إن وُجد الحساب سيتم إرسال رابط/رمز إعادة التعيين إلى البريد.');
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = $this->auth->resetPassword($data);

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return ApiResponse::success(null, 'تم إعادة تعيين كلمة المرور.');
    }
}

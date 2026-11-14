<?php

namespace App\Http\Requests\Company;

use App\Enums\CompanyUserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = Auth::guard('company')->user() ?? $this->user();

        return $user?->can('staff.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('company_users', 'email')],
            'phone' => ['required', 'string', 'max:30', Rule::unique('company_users', 'phone')],
            'role' => ['required', Rule::enum(CompanyUserRole::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'الاسم',
            'email' => 'البريد',
            'phone' => 'الهاتف',
            'role' => 'الدور',
            'password' => 'كلمة المرور',
        ];
    }
}

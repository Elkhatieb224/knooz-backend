<?php

namespace App\Http\Requests\Company;

use App\Enums\Theme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user('company')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('company_users', 'email')->ignore($userId)],
            'phone' => ['required', 'string', 'max:30', Rule::unique('company_users', 'phone')->ignore($userId)],
            'theme' => ['required', Rule::enum(Theme::class)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }
}

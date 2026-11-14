<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.manage')
            || $this->user()?->can('users.activate');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        if ($this->user()?->can('users.manage')) {
            return [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
                'phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($userId)],
                'password' => ['nullable', 'confirmed', Password::defaults()],
                'status' => ['required', Rule::enum(UserStatus::class)],
            ];
        }

        return [
            'status' => ['required', Rule::enum(UserStatus::class)],
        ];
    }
}

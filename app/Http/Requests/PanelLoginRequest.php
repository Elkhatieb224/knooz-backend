<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PanelLoginRequest extends FormRequest
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
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'login' => 'البريد أو الهاتف',
            'password' => 'كلمة المرور',
        ];
    }
}

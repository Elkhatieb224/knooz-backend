<?php

namespace App\Http\Requests\Api\Center;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
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
            'subject' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:5000'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'operation_id' => ['nullable', 'integer', 'exists:operations,id'],
        ];
    }
}

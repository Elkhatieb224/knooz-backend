<?php

namespace App\Http\Requests\Api\Center;

use App\Enums\OperationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOperationRequest extends FormRequest
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
            'status' => ['sometimes', Rule::enum(OperationStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

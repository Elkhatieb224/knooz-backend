<?php

namespace App\Http\Requests\Center;

use App\Enums\OperationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOperationStatusRequest extends FormRequest
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
            'status' => ['required', Rule::enum(OperationStatus::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}

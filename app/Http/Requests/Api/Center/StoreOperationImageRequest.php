<?php

namespace App\Http\Requests\Api\Center;

use App\Enums\OperationImageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOperationImageRequest extends FormRequest
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
            'type' => ['required', Rule::enum(OperationImageType::class)],
            'image' => ['required', 'image', 'max:5120'],
        ];
    }
}

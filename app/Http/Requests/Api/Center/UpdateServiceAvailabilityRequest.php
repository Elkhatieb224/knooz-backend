<?php

namespace App\Http\Requests\Api\Center;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceAvailabilityRequest extends FormRequest
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
            'is_available' => ['required', 'boolean'],
        ];
    }
}

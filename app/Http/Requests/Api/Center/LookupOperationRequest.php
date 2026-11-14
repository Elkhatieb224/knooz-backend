<?php

namespace App\Http\Requests\Api\Center;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LookupOperationRequest extends FormRequest
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
            'method' => ['required', 'string', Rule::in(['qr', 'nfc', 'plate'])],
            'value' => ['required', 'string', 'max:191'],
        ];
    }
}

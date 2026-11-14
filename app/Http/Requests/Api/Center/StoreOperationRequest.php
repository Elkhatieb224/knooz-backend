<?php

namespace App\Http\Requests\Api\Center;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOperationRequest extends FormRequest
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
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer', 'exists:services,id'],
            'booking_id' => ['nullable', 'integer', 'exists:bookings,id'],
            'payment_mode' => ['nullable', 'string', Rule::in(['wallet', 'subscription', 'cash', 'credit'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'source' => ['nullable', 'string', Rule::in(['booking', 'qr', 'nfc', 'manual'])],
            'verification_code' => ['nullable', 'string', 'max:32'],
        ];
    }
}

<?php

namespace App\Http\Requests\Api\Center;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingRequest extends FormRequest
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
            'status' => ['required', Rule::in([
                BookingStatus::InProgress->value,
                BookingStatus::Cancelled->value,
                BookingStatus::Completed->value,
            ])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

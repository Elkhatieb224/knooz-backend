<?php

namespace App\Http\Requests\Admin;

use App\Enums\CompanyStatus;
use App\Enums\PaymentMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('companies.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'commercial_register' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', Rule::enum(CompanyStatus::class)],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'usage_cap' => ['nullable', 'numeric', 'min:0'],
            'payment_mode' => ['required', Rule::enum(PaymentMode::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}

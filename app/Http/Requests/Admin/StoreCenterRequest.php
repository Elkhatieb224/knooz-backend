<?php

namespace App\Http\Requests\Admin;

use App\Enums\CenterStatus;
use App\Enums\CenterType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('centers.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(CenterType::class)],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'status' => ['required', Rule::enum(CenterStatus::class)],
            'show_revenue' => ['nullable', 'boolean'],
            'manager_center_user_id' => ['nullable', 'exists:center_users,id'],
            'services' => ['nullable', 'array'],
            'services.*.enabled' => ['nullable'],
            'services.*.contract_price' => ['nullable', 'numeric', 'min:0'],
            'services.*.app_profit' => ['nullable', 'numeric', 'min:0'],
            'services.*.is_available' => ['nullable'],
        ];
    }
}

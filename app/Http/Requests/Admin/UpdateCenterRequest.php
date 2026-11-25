<?php

namespace App\Http\Requests\Admin;

use App\Enums\CenterStatus;
use App\Enums\CenterType;
use App\Support\CenterServiceCommission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCenterRequest extends FormRequest
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
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['required', Rule::enum(CenterStatus::class)],
            'offers_center_service' => ['sometimes', 'boolean'],
            'offers_home_service' => ['sometimes', 'boolean'],
            'offers_emergency_service' => ['sometimes', 'boolean'],
            'show_revenue' => ['nullable', 'boolean'],
            'product_app_profit' => ['nullable', 'numeric', 'min:0'],
            'product_app_profit_mode' => ['nullable', 'string', Rule::in([
                CenterServiceCommission::MODE_FIXED,
                CenterServiceCommission::MODE_PERCENT,
            ])],
            'manager_center_user_id' => ['nullable', 'exists:center_users,id'],
            'services' => ['nullable', 'array'],
            'services.*.enabled' => ['nullable'],
            'services.*.contract_price' => ['nullable', 'numeric', 'min:0'],
            'services.*.app_profit_mode' => ['nullable', 'in:fixed,percent'],
            'services.*.app_profit' => ['nullable', 'numeric', 'min:0'],
            'services.*.is_available' => ['nullable'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ((array) $this->input('services', []) as $serviceId => $row) {
                if (empty($row['enabled'])) {
                    continue;
                }
                $mode = ($row['app_profit_mode'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
                $value = $row['app_profit'] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }
                if ($mode === 'percent' && (float) $value > 100) {
                    $validator->errors()->add(
                        "services.{$serviceId}.app_profit",
                        'نسبة عمولة التطبيق لا تتجاوز 100%.'
                    );
                }
            }

            $productMode = ($this->input('product_app_profit_mode') ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
            $productProfit = $this->input('product_app_profit');
            if ($productProfit !== null && $productProfit !== '' && $productMode === 'percent' && (float) $productProfit > 100) {
                $validator->errors()->add('product_app_profit', 'نسبة عمولة منتجات المركز لا تتجاوز 100%.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $lat = $this->input('lat');
        $lng = $this->input('lng');
        $productProfit = $this->input('product_app_profit');

        $this->merge([
            'offers_center_service' => $this->boolean('offers_center_service'),
            'offers_home_service' => $this->boolean('offers_home_service'),
            'offers_emergency_service' => $this->boolean('offers_emergency_service'),
            'show_revenue' => $this->boolean('show_revenue'),
            'product_app_profit_mode' => CenterServiceCommission::normalizeMode($this->input('product_app_profit_mode')),
            'product_app_profit' => $this->normalizeProductProfit($productProfit),
            'lat' => (is_string($lat) && trim($lat) === '') || $lat === null ? null : $lat,
            'lng' => (is_string($lng) && trim($lng) === '') || $lng === null ? null : $lng,
        ]);
    }

    private function normalizeProductProfit(mixed $productProfit): mixed
    {
        if ((is_string($productProfit) && trim($productProfit) === '') || $productProfit === null) {
            return null;
        }

        $mode = CenterServiceCommission::normalizeMode($this->input('product_app_profit_mode'));
        $value = (float) $productProfit;

        return $mode === CenterServiceCommission::MODE_PERCENT ? min(100, $value) : $value;
    }
}

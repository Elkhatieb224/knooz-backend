<?php

namespace App\Http\Requests\Admin;

use App\Enums\ShopProductCategory;
use App\Support\CenterServiceCommission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShopProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('shop.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'center_id' => ['nullable', 'integer', 'exists:centers,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::enum(ShopProductCategory::class)],
            'brand' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'use_custom_app_profit' => ['sometimes', 'boolean'],
            'app_profit' => ['nullable', 'numeric', 'min:0'],
            'app_profit_mode' => ['nullable', 'string', Rule::in([
                CenterServiceCommission::MODE_FIXED,
                CenterServiceCommission::MODE_PERCENT,
            ])],
            'image' => ['nullable', 'image', 'max:4096'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'meta_notes' => ['nullable', 'string', 'max:20000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->boolean('use_custom_app_profit')) {
                return;
            }
            $mode = CenterServiceCommission::normalizeMode($this->input('app_profit_mode'));
            $value = $this->input('app_profit');
            if ($value === null || $value === '') {
                $validator->errors()->add('app_profit', 'أدخل قيمة العمولة المخصصة.');

                return;
            }
            if ($mode === CenterServiceCommission::MODE_PERCENT && (float) $value > 100) {
                $validator->errors()->add('app_profit', 'نسبة عمولة المنتج لا تتجاوز 100%.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $useCustom = $this->boolean('use_custom_app_profit');

        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'use_custom_app_profit' => $useCustom,
            'center_id' => $this->filled('center_id') ? $this->integer('center_id') : null,
            'app_profit_mode' => CenterServiceCommission::normalizeMode($this->input('app_profit_mode')),
            'app_profit' => $useCustom ? $this->input('app_profit') : null,
        ]);

        // Slug is server-generated only — ignore any client value.
        $this->request->remove('slug');
    }
}

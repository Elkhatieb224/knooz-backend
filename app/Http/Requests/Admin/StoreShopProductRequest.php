<?php

namespace App\Http\Requests\Admin;

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
        $productId = $this->route('shop_product')?->id;

        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('shop_products', 'slug')->ignore($productId),
            ],
            'category' => ['required', 'string', Rule::in(['tires', 'oils'])],
            'brand' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'meta' => ['nullable'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}

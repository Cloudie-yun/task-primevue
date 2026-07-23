<?php

namespace App\Http\Requests\Product;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ProductStatus;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'slug' => [
                'nullable', 'string', 'max:255',
                Rule::unique('products', 'slug')->ignore($this->route('product')),
            ],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(ProductStatus::class)],

            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,avif', 'max:5120'],

            'attribute_value_ids' => ['array'],
            'attribute_value_ids.*' => [
                'integer',
                Rule::exists('variant_values', 'id')->where(function ($query) {
                    $query->whereHas('type', fn ($q) => $q->where('generates_variants', false));
                }),
            ],
                        
            'variants' => ['nullable', 'array'],
            'variants.*.sku' => ['nullable', 'string', 'max:255', 'distinct', Rule::unique('product_variants', 'sku')],
            'variants.*.additional_price' => ['required', 'numeric'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.values' => ['required', 'array', 'min:1'],
            'variants.*.values.*.id' => ['required', 'exists:variant_values,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Product slug is in use, please change product name',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('name')),
        ]);
    }
}

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

            'selections' => ['required', 'array','min:1'],
            'selections.*' => ['required','array', 'min:1'],
            'selections.*.*' => ['required','integer', 'exists:variant_values,id'],

            // 'sku' => [
            //     'required',
            //     'string',
            //     'max:255',
            //     Rule::unique('product_variants', 'sku')->ignore($this->route('variant')),
            // ],
            
            // 'additional_price' => ['required', 'numeric'],
            // 'stock' => ['required', 'integer', 'min:0'],
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

<?php

namespace App\Http\Requests\Product;

use Illuminate\Support\Str;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ProductStatus;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use App\Models\ProductVariant;

class UpdateProductRequest extends FormRequest
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
            'category_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,avif', 'max:5120'],
            'deleted_image_ids' => ['nullable', 'array'],
            'deleted_image_ids.*' => [
                'integer',
                Rule::exists('media', 'id')->where(function ($query) {
                    $query->where('model_id', $this->route('product')->id)
                        ->where('model_type', \App\Models\Product::class);
                }),
            ],
            
            'attribute_value_ids' => ['array'],
            'attribute_value_ids.*' => [
                'integer',
                Rule::exists('variant_values', 'id')->where(function ($query) {
                    $query->whereHas('type', fn ($q) => $q->where('generates_variants', false));
                }),
            ],

            'variants' => ['array'],
            'variants.*.id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'variants.*.sku' => [
                'nullable', 'string', 'max:255', 'distinct',
                function ($attribute, $value, $fail) {
                    // e.g. $attribute = "variants.2.sku"
                    preg_match('/variants\.(\d+)\.sku/', $attribute, $m);
                    $index = $m[1] ?? null;
                    $rowId = $index !== null ? $this->input("variants.{$index}.id") : null;

                    $exists = ProductVariant::where('sku', $value)
                        ->when($rowId, fn ($q) => $q->where('id', '!=', $rowId))
                        ->exists();

                    if ($exists) {
                        $fail('This SKU is already in use by another variant.');
                    }
                },
            ],
            'variants.*.additional_price' => ['required', 'numeric'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.values' => ['required', 'array', 'min:1'],
            'variants.*.values.*.id' => ['required', 'exists:variant_values,id'],

            'deleted_variant_ids' => ['array'],
            'deleted_variant_ids.*' => ['integer', 'exists:product_variants,id'],
        ];
    }
    
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $product = $this->route('product');
            $newImageCount = count($this->file('images') ?? []);
            $deletedCount = count($this->input('deleted_image_ids') ?? []);
            $existingCount = $product->getMedia('images')->count();

            if (($existingCount - $deletedCount) + $newImageCount <= 0) {
                $validator->errors()->add('images', 'At least one product image is required.');
            }
        });
    }
}

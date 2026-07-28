<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')->id;

        return [
            'variants' => ['array'],
            'variants.*.id' => [
                'required',
                'integer',
                Rule::exists('product_variants', 'id')->where('product_id', $productId),
            ],
            'variants.*.additional_price' => ['required', 'numeric', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],

            'deleted_variant_ids' => ['array'],
            'deleted_variant_ids.*' => [
                'integer',
                Rule::exists('product_variants', 'id')->where('product_id', $productId),
            ],
        ];
    }
}
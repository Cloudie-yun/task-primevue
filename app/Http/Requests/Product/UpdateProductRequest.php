<?php

namespace App\Http\Requests\Product;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\VariantValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
{
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
                        ->where('model_type', Product::class);
                }),
            ],

            // Job 2 — flat descriptive attributes
            'attribute_value_ids' => ['nullable', 'array'],
            'attribute_value_ids.*' => ['integer', 'exists:variant_values,id'],

            // Job 1 — purchasable combinations, same shape as StoreProductRequest
            'variant_selections' => ['nullable', 'array'],
            'variant_selections.*' => ['array', 'min:1'],
            'variant_selections.*.*' => ['integer', 'exists:variant_values,id'],

            // Confirmed-by-frontend list of variants about to be deleted
            'deleted_variant_ids' => ['nullable', 'array'],
            'deleted_variant_ids.*' => [
                'integer',
                Rule::exists('product_variants', 'id')->where('product_id', $this->route('product')->id),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->ensureImageCount($validator);
            $this->ensureValueTypesAreConsistent($validator);
        });
    }

    protected function ensureImageCount(Validator $validator): void
    {
        $product = $this->route('product');
        $newImageCount = count($this->file('images') ?? []);
        $deletedCount = count($this->input('deleted_image_ids') ?? []);
        $existingCount = $product->getMedia('images')->count();

        if (($existingCount - $deletedCount) + $newImageCount <= 0) {
            $validator->errors()->add('images', 'At least one product image is required.');
        }
    }

    /**
     * Same guard as StoreProductRequest: values used in variant_selections must
     * belong to a generating type, values in attribute_value_ids must not.
     */
    protected function ensureValueTypesAreConsistent(Validator $validator): void
    {
        $selections = $this->input('variant_selections', []);
        $attributeIds = collect($this->input('attribute_value_ids', []))->unique();

        $selectionIds = collect($selections)->flatten()->unique();
        $allIds = $selectionIds->merge($attributeIds);

        if ($allIds->isEmpty()) {
            return;
        }

        $values = VariantValue::with('type')->whereIn('id', $allIds)->get()->keyBy('id');

        foreach ($selections as $typeId => $valueIds) {
            foreach ($valueIds as $valueId) {
                $value = $values->get($valueId);
                if (! $value) {
                    continue; // caught by exists: rule already
                }

                if (! $value->type->generates_variants) {
                    $validator->errors()->add(
                        "variant_selections.{$typeId}",
                        "'{$value->value}' belongs to a non-generating type and can't be used as a variant combination."
                    );
                }

                if ((int) $value->variant_type_id !== (int) $typeId) {
                    $validator->errors()->add(
                        "variant_selections.{$typeId}",
                        "'{$value->value}' does not belong to the selected type."
                    );
                }
            }
        }

        foreach ($attributeIds as $valueId) {
            $value = $values->get($valueId);
            if ($value && $value->type->generates_variants) {
                $validator->errors()->add(
                    'attribute_value_ids',
                    "'{$value->value}' belongs to a generating type and must be part of a variant combination instead."
                );
            }
        }
    }
}
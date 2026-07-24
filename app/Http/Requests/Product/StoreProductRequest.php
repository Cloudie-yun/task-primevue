<?php

namespace App\Http\Requests\Product;

use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use App\Models\VariantValue;

class StoreProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(ProductStatus::class)],

            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,avif', 'max:5120'],

            // Job 2 — flat descriptive attributes
            'attribute_values' => ['nullable', 'array'],
            'attribute_values.*' => ['integer', 'exists:variant_values,id'],

            // Job 1 — purchasable combinations
            'variant_selections' => ['required', 'min:1', 'array'],
            'variant_selections.*' => ['array', 'min:1'],
            'variant_selections.*.*' => ['integer', 'exists:variant_values,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->ensureNoDuplicateCombinations($validator);
            $this->ensureValueTypesAreConsistent($validator);
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('name')),
        ]);
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Product slug is in use, please change product name',
        ];
    }
    
    /**
     * Two variant rows can't both be e.g. [Size:S, Color:Red] — that's the
     * same purchasable combination represented twice.
     */
    protected function ensureNoDuplicateCombinations(Validator $validator): void
    {
        $seen = [];

        foreach ($this->input('variants', []) as $index => $variant) {
            $key = collect($variant['value_ids'] ?? [])->sort()->implode('-');

            if (isset($seen[$key])) {
                $validator->errors()->add(
                    "variants.{$index}.value_ids",
                    'This combination of values is already used by another variant.'
                );
            }

            $seen[$key] = true;
        }
    }

    /**
     * Guard against value_ids leaking across the two jobs:
     * - variants.*.value_ids must only reference values whose type generates variants
     * - attribute_values must only reference values whose type does NOT generate variants
     * - a single variant row can't contain two values from the same type (e.g. two Sizes)
     */
    protected function ensureValueTypesAreConsistent(Validator $validator): void
    {
        $variantValueIds = collect($this->input('variants', []))
            ->pluck('value_ids')
            ->flatten()
            ->unique();

        $attributeValueIds = collect($this->input('attribute_values', []))->unique();

        $allIds = $variantValueIds->merge($attributeValueIds);

        if ($allIds->isEmpty()) {
            return;
        }

        $values = VariantValue::with('type')
            ->whereIn('id', $allIds)
            ->get()
            ->keyBy('id');

        foreach ($this->input('variants', []) as $index => $variant) {
            $seenTypes = [];

            foreach ($variant['value_ids'] ?? [] as $valueId) {
                $value = $values->get($valueId);
                if (! $value) {
                    continue; // already caught by exists: rule
                }

                if (! $value->type->generates_variants) {
                    $validator->errors()->add(
                        "variants.{$index}.value_ids",
                        "'{$value->value}' belongs to a non-generating type and can't be used in a variant combination."
                    );
                }

                if (isset($seenTypes[$value->variant_type_id])) {
                    $validator->errors()->add(
                        "variants.{$index}.value_ids",
                        "A variant can only have one value per type ('{$value->type->name}' is used twice)."
                    );
                }

                $seenTypes[$value->variant_type_id] = true;
            }
        }

        foreach ($this->input('attribute_values', []) as $index => $valueId) {
            $value = $values->get($valueId);
            if ($value && $value->type->generates_variants) {
                $validator->errors()->add(
                    "attribute_values.{$index}",
                    "'{$value->value}' belongs to a generating type and must be part of a variant combination instead."
                );
            }
        }
    }
}
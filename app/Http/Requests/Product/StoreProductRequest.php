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
            'category_id' => ['nullable', 'exists:categories,id'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'image' => ['nullable', 'image', 'max:5120'], // 5MB

            'selections' => ['nullable', 'array'],
            'selections.*' => ['array', 'min:1'],
            'selections.*.*' => ['integer', 'exists:variant_values,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('name')),
        ]);
    }
}

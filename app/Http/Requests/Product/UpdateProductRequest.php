<?php

namespace App\Http\Requests\Product;

use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ProductStatus;

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
            'image' => ['nullable', 'image', 'max:5120'],
        ];
    }
    
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('name')),
        ]);
    }
}

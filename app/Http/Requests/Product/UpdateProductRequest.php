<?php

namespace App\Http\Requests\Product;

use Illuminate\Support\Str;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ProductStatus;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

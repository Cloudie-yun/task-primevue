<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class GenerateProductVariantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Shape: { "1": [4, 5], "2": [8, 9, 10] }
            // key = variant_type_id, value = array of selected variant_value_ids for that type
            'selections' => ['required', 'array', 'min:1'],
            'selections.*' => ['required', 'array', 'min:1'],
            'selections.*.*' => ['required', 'integer', 'exists:variant_values,id'],
        ];
    }
}
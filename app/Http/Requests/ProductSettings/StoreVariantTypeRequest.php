<?php

namespace App\Http\Requests\ProductSettings;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVariantTypeRequest extends FormRequest
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
            'name' => [
                'required', 
                'string', 
                'max:255', 
                Rule::unique('variant_types', 'name'),
            ],
            'generates_variants' => ['sometimes', 'boolean'],
            'values' => ['required', 'array', 'min:1'],
            'values.*' => ['required', 'string', 'max:255', 'distinct'],
        ];
    }
}

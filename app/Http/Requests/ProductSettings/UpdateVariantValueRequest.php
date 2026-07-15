<?php

namespace App\Http\Requests\ProductSettings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVariantValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $variantValue = $this->route('variantValue');

        return [
            'value' => [
                'required',
                'string',
                'max:255',
                Rule::unique('variant_values', 'value')
                    ->where('variant_type_id', $variantValue->variant_type_id)
                    ->ignore($variantValue->id),
            ],
        ];
    }
}
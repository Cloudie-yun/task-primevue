<?php

namespace App\Http\Requests\ProductSettings;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVariantValueRequest extends FormRequest
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
            'value' => [
                'required',
                'string',
                'max:255',
                Rule::unique('variant_values', 'value')
                    ->where('variant_type_id', $this->route('variantType')->id),
            ],
        ];
    }
}

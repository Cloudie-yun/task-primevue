<?php

namespace App\Http\Requests\ProductSettings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVariantTypeRequest extends FormRequest
{
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
                Rule::unique('variant_types', 'name')->ignore($this->route('variantType')),
            ],
            'generates_variants' => ['sometimes', 'boolean'],
            'values' => ['required', 'array', 'min:1'],
            'values.*.id' => ['nullable', 'integer', 'exists:variant_values,id'],
            'values.*.value' => ['required', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $values = collect($this->input('values', []))->pluck('value')->map(fn ($v) => strtolower(trim($v)));

            if ($values->count() !== $values->unique()->count()) {
                $validator->errors()->add('values', 'Values must be unique within this type.');
            }
        });
    }
}
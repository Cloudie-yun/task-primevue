<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateVariantValueRequest;
use App\Models\VariantValue;

class VariantValueController extends Controller
{
    public function update(UpdateVariantValueRequest $request, VariantValue $variantValue)
    {
        $variantValue->update($request->validated());

        return back()->with('success', 'Value updated.');
    }

    public function destroy(VariantValue $variantValue)
    {
        $variantValue->delete();

        return back()->with('success', 'Value deleted.');
    }
}
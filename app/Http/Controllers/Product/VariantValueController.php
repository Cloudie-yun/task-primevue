<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductSettings\StoreVariantValueRequest;
use App\Http\Requests\ProductSettings\UpdateVariantValueRequest;
use App\Models\VariantType;
use App\Models\VariantValue;

class VariantValueController extends Controller
{
    public function store(StoreVariantValueRequest $request, VariantType $variantType)
    {
        $variantType->values()->create($request->validated());

        return back()->with('success', 'Value added.');
    }

    public function update(UpdateVariantValueRequest $request, VariantValue $variantValue)
    {
        $variantValue->update($request->validated());

        return back()->with('success', 'Value updated.');
    }

    public function destroy(VariantValue $variantValue)
    {
        $variants = $value->productVariants()->get(); // however that relation is named

        if ($variants->isNotEmpty() && !$request->boolean('force')) {
            return back()->withErrors([
                'value' => "Used by {$variants->count()} variant(s): {$variants->pluck('sku')->implode(', ')}",
            ]);
        }

        DB::transaction(function () use ($value, $variants) {
            $variants->each->delete();
            $value->delete();
        });
    }
}
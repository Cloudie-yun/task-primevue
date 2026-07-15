<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\GenerateProductVariantsRequest;
use App\Http\Requests\Product\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductVariantGenerator;

class ProductVariantController extends Controller
{
    public function __construct(private ProductVariantGenerator $generator) {}

    public function generate(GenerateProductVariantsRequest $request, Product $product)
    {
        $created = $this->generator->generate($product, $request->validated('selections'));

        return back()->with('success', "{$created} variant(s) generated.");
    }

    public function update(UpdateProductVariantRequest $request, ProductVariant $productVariant)
    {
        $productVariant->update($request->validated());

        return back()->with('success', 'Variant updated.');
    }

    public function destroy(ProductVariant $productVariant)
    {
        $productVariant->delete();

        return back()->with('success', 'Variant deleted.');
    }
}
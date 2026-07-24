<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\GenerateProductVariantsRequest;
use App\Http\Requests\Product\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductVariantGenerator;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductVariantController extends Controller
{
    public function index(Request $request, ?Product $product = null)
    {
        if ($product) {
            $variants = ProductVariant::query()
                ->with(['values.type'])
                ->where('product_id', $product->id)
                ->orderBy('id')
                ->get();

            return Inertia::render('products/variants/Index', [
                'product' => $product->only(['id', 'name', 'category_id','slug']),
                'variants' => $variants,
                'products' => [],
            ]);
        }

        // Unscoped: paginate by product, then eager-load each page's variants in full.
        $productIds = Product::query()
            ->orderBy('name')
            ->paginate(15, ['id'], 'page')
            ->through(fn ($p) => $p->id);

        $variants = ProductVariant::query()
            ->with(['product:id,name', 'values.type'])
            ->whereIn('product_id', $productIds->items())
            ->orderBy('product_id')
            ->orderBy('id')
            ->get();

        return Inertia::render('products/variants/Index', [
            'product' => null,
            'variants' => $variants,
            'productsPage' => $productIds, // paginator meta: current_page, last_page, links, etc.
            'products' => Product::query()->orderBy('name')->get(['id', 'name','slug']), // for the filter dropdown, unrelated to grouping pagination
        ]);
    }
    
    public function generate(GenerateProductVariantsRequest $request, Product $product)
    {
        $created = $this->generator->generate($product, $request->validated('variant_selections'));

        return back()->with('success', "{$created} variant(s) generated.");
    }

    public function update(UpdateProductVariantRequest $request, Product $product, ProductVariant $variant)
    {
        $variant->update($request->validated());

        return back()->with('success', 'Variant updated.');
    }

    public function destroy(Product $product, ProductVariant $variant)
    {
        $variant->delete();

        return back()->with('success', 'Variant deleted.');
    }
}
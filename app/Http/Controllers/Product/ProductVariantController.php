<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\GenerateProductVariantsRequest;
use App\Http\Requests\Product\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductVariantGenerator;
use App\Actions\UpdateProductVariantsAction;
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
                'product' => $product->only(['id', 'slug', 'name', 'category_id']),
                'variants' => $variants,
                'products' => [],
                'cameFromBrowse' => $request->query('from') === 'browse'
            ]);
        }

        $variants = ProductVariant::query()
            ->with(['product:id,name', 'values.type'])
            ->orderBy('product_id')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        $products = Product::query()
            ->with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'category_id'])
            ->groupBy(fn ($p) => $p->category?->name ?? 'Uncategorized')
            ->map(fn ($items, $categoryName) => [
                'label' => $categoryName,
                'items' => $items->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                ])->values(),
            ])
            ->values();

        return Inertia::render('products/variants/Index', [
            'product' => null,
            'variants' => $variants, // now a paginator, same shape as ProductController::index()
            'products' => $products, // for the filter dropdown, unrelated to variants pagination
        ]);
    }
    
    public function update(UpdateProductVariantRequest $request, Product $product, UpdateProductVariantsAction $action)
    {
        $action->handle(
            $product,
            $request->validated('variants', []),
            $request->validated('deleted_variant_ids', [])
        );

        return back()->with('success', 'Variants updated.');
    }
}
<?php

namespace App\Http\Controllers\Product;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\VariantType;
use App\Services\ProductVariantGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function __construct(private ProductVariantGenerator $generator) {}
    public function index()
    {
        return Inertia::render('products/Products', [
            'products' => Product::query()
                ->with(['category', 'media'])
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create()
    {
        return Inertia::render('products/Create', [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => collect(ProductStatus::cases())->map(fn ($status) => [
                'label' => ucfirst($status->value),
                'value' => $status->value,
            ]),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $product = DB::transaction(function () use ($request) {
            $product = Product::create($request->safe()->except(['image', 'selections']));

            if ($request->hasFile('image')) {
                $product->addMediaFromRequest('image')->toMediaCollection('image');
            }

            if ($request->filled('selections')) {
                $this->generator->generate($product, $request->validated('selections'));
            }

            return $product;
        });

        return to_route('products.edit', $product)->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        return Inertia::render('products/Edit', [
            'product' => $product->load(['category', 'media', 'variants.values.type']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'variantTypes' => VariantType::with('values')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->safe()->except('image'));

        if ($request->hasFile('image')) {
            $product->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return back()->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return back()->with('success', 'Product deleted.');
    }
}

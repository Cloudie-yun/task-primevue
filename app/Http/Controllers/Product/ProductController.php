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
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'variantTypes' => VariantType::with('values')->orderBy('name')->get(),
            'statuses' => collect(ProductStatus::cases())->map(fn ($status) => [
                'label' => ucfirst($status->value),
                'value' => $status->value,
            ]),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $product = DB::transaction(function () use ($request) {
            $product = Product::create($request->safe()->except(['images', 'selections']));

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $product->addMedia($image)
                        ->usingName("{$product->name} image")
                        ->usingFileName($product->generateImageFileName($image, $index))
                        ->toMediaCollection('images');
                }
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
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'variantTypes' => VariantType::with('values')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        DB::transaction(function () use ($request, $product) {
            $product->update($request->safe()->except(['images', 'deleted_image_ids']));

            if ($request->filled('deleted_image_ids')) {
                $product->media()
                    ->whereIn('id', $request->validated('deleted_image_ids'))
                    ->get()
                    ->each(fn ($media) => $media->delete());
            }

            if ($request->hasFile('images')) {
                // Continue numbering from however many images already exist,
                // so re-uploads after a partial delete don't collide with existing file names
                $startIndex = $product->getMedia('images')->count();

                foreach ($request->file('images') as $i => $image) {
                    $product->addMedia($image)
                        ->usingName("{$product->name} image")
                        ->usingFileName($product->generateImageFileName($image, $startIndex + $i))
                        ->toMediaCollection('images');
                }
            }
        });

        return back()->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return back()->with('success', 'Product deleted.');
    }
}

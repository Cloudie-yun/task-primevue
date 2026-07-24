<?php

namespace App\Http\Controllers\Product;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Actions\SyncProductAttributesAction;
use App\Actions\SyncProductVariantsAction;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantType;
use App\Models\VariantValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
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

    public function store(
        StoreProductRequest $request,
        SyncProductVariantsAction $syncVariants,
        SyncProductAttributesAction $syncAttributes
    ) {
        $product = DB::transaction(function () use ($request, $syncVariants, $syncAttributes) {
            $product = Product::create(
                $request->safe()->except(['images', 'variant_selections', 'deleted_variant_ids', 'attribute_value_ids'])
            );

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $product->addMedia($image)
                        ->usingName("{$product->name} image")
                        ->usingFileName($product->generateImageFileName($image, $index))
                        ->toMediaCollection('images');
                }
            }

            $syncVariants->handle($product, $request->input('variant_selections', []));
            $syncAttributes->handle($product, $request->input('attribute_value_ids', []));

            return $product;
        });

        return to_route('products.edit', $product)->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        return Inertia::render('products/Edit', [
            'product' => $product->load(['category', 'media', 'variants.values.type', 'attributeValues.type']),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'variantTypes' => VariantType::with('values')->orderBy('name')->get(),
        ]);
    }

    public function variants(Product $product)
    {
        return Inertia::render('products/VariantsPage', [
            'product' => $product->load(['category', 'media', 'variants.values.type']),
            'variantTypes' => VariantType::with('values')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product, SyncProductVariantsAction $syncVariants, SyncProductAttributesAction $syncAttributes)
    {
        DB::transaction(function () use ($request, $product, $syncVariants, $syncAttributes) {
            $product->update(
                $request->safe()->except([
                    'images', 'deleted_image_ids', 'variant_selections', 'attribute_value_ids', 'deleted_variant_ids',
                ])
            );

            if ($request->filled('deleted_image_ids')) {
                $product->media()
                    ->whereIn('id', $request->validated('deleted_image_ids'))
                    ->get()
                    ->each(fn ($media) => $media->delete());
            }

            if ($request->hasFile('images')) {
                $startIndex = $product->getMedia('images')->count();
                foreach ($request->file('images') as $i => $image) {
                    $product->addMedia($image)
                        ->usingName("{$product->name} image")
                        ->usingFileName($product->generateImageFileName($image, $startIndex + $i))
                        ->toMediaCollection('images');
                }
            }

            $syncVariants->handle(
                $product,
                $request->input('variant_selections', []),
                $request->input('deleted_variant_ids', [])
            );

            $syncAttributes->handle($product, $request->input('attribute_value_ids', []));
        });

        return to_route('products.edit', $product)->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return back()->with('success', 'Product deleted.');
    }
}

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
                ->paginate(10)
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

    public function preview(Product $product)
    {
        $product->load([
            'category',
            'media',
            'attributeValues.type',
            'variants.values.type',
        ]);

        $staticAttributes = $product->attributeValues
            ->filter(fn ($value) => ! $value->type->generates_variants)
            ->map(fn ($value) => [
                'id' => $value->id,
                'value' => $value->value,
                'type' => ['id' => $value->type->id, 'name' => $value->type->name],
            ])
            ->values();

        // Build the generating variant types (Color, Size, etc.) purely from what
        // actually appears on this product's variants — not all VariantType rows.
        $variantTypes = [];
        foreach ($product->variants as $variant) {
            foreach ($variant->values as $value) {
                if (! $value->type->generates_variants) {
                    continue;
                }

                $variantTypes[$value->variant_type_id] ??= [
                    'id' => $value->variant_type_id,
                    'name' => $value->type->name,
                    'values' => [],
                ];

                $variantTypes[$value->variant_type_id]['values'][$value->id] ??= [
                    'id' => $value->id,
                    'value' => $value->value,
                    'sort_order' => $value->sort_order,
                ];
            }
        }

        $variantTypes = collect($variantTypes)
            ->map(fn ($type) => [
                ...$type,
                'values' => collect($type['values'])->sortBy('sort_order')->values(),
            ])
            ->values();

        return Inertia::render('products/Preview', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'price' => $product->price,
                'status' => $product->status,
                'category' => $product->category
                    ? ['id' => $product->category->id, 'name' => $product->category->name]
                    : null,
                'images' => $product->getMedia('images')->map(fn ($media) => [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                ])->values(),
                'static_attributes' => $staticAttributes,
                'variant_types' => $variantTypes,
                'variants' => $product->variants->map(fn ($variant) => [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'additional_price' => $variant->additional_price,
                    'stock' => $variant->stock,
                    'value_ids' => $variant->values->pluck('id')->values(),
                ]),
            ],
        ]);
    }
}

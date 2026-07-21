<?php

namespace App\Http\Controllers\Product;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantType;
use App\Models\VariantValue;
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
            $product = Product::create($request->safe()->except(['images', 'selections', 'variants', 'deleted_variant_ids']));

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

            $this->syncVariants($product, $request->input('variants', []));

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

    public function variants(Product $product)
    {
        return Inertia::render('products/VariantsPage', [
            'product' => $product->load(['category', 'media', 'variants.values.type']),
            'variantTypes' => VariantType::with('values')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        DB::transaction(function () use ($request, $product) {
            $product->update($request->safe()->except(['images', 'deleted_image_ids', 'variants', 'deleted_variant_ids']));

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
            // if ($request->filled('selections')) {
            //     $this->generator->generate($product, $request->validated('selections'));
            // }

            $this->syncVariants($product, $request->input('variants', []), $request->input('deleted_variant_ids', []));
        });

        return back()->with('success', 'Product updated.');
    }

    protected function syncVariants(Product $product, array $workingVariants, array $deletedVariantIds = []): void
    {
        if ($deletedVariantIds) {
            $product->variants()
                ->whereIn('id', array_map('intval', $deletedVariantIds))
                ->delete();
        }

        foreach ($workingVariants as $variantData) {
            $variantValues = collect($variantData['values'] ?? [])
                ->map(fn ($value) => (int) $value['id'])
                ->filter()
                ->values()
                ->all();

            if (! $variantValues) {
                continue;
            }

            $variant = null;
            if (! empty($variantData['id'])) {
                $variant = $product->variants()->find((int) $variantData['id']);
            }

            if (! $variant) {
                $variant = $product->variants()->create([
                    'sku' => $this->resolveVariantSku($product, $variantData['sku'] ?? '', $variantValues),
                    'additional_price' => (float) ($variantData['additional_price'] ?? 0),
                    'stock' => (int) ($variantData['stock'] ?? 0),
                ]);
            } else {
                $variant->update([
                    'sku' => $this->resolveVariantSku($product, $variantData['sku'] ?? $variant->sku, $variantValues, $variant),
                    'additional_price' => (float) ($variantData['additional_price'] ?? $variant->additional_price),
                    'stock' => (int) ($variantData['stock'] ?? $variant->stock),
                ]);
            }

            $variant->values()->sync($variantValues);
        }
    }

    protected function resolveVariantSku(Product $product, string $sku, array $variantValues, ?ProductVariant $existingVariant = null): string
    {
        $candidate = trim($sku);

        if ($candidate === '') {
            $candidate = $this->generator->generateSkuForValues($product, $variantValues);
        }

        if ($existingVariant && $existingVariant->sku === $candidate) {
            return $candidate;
        }

        $baseCandidate = $candidate;
        $suffix = 1;

        while (ProductVariant::where('sku', $candidate)->where('id', '!=', $existingVariant?->id ?? 0)->exists()) {
            $candidate = "{$baseCandidate}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return back()->with('success', 'Product deleted.');
    }
}

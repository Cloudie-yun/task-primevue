<?php

namespace App\Actions;

use App\Models\Product;
use App\Services\ProductVariantSkuGenerator;
use Illuminate\Support\Facades\DB;

class SyncProductVariantsAction
{
    public function __construct(private ProductVariantSkuGenerator $skuGenerator) {}

    public function handle(Product $product, array $selections, array $deletedVariantIds = []): void
    {
        DB::transaction(function () use ($product, $selections, $deletedVariantIds) {
            if ($deletedVariantIds) {
                $product->variants()
                    ->whereIn('id', array_map('intval', $deletedVariantIds))
                    ->delete();
            }

            $selections = collect($selections)
                ->map(fn ($ids) => array_values(array_unique(array_map('intval', $ids))))
                ->filter(fn ($ids) => count($ids) > 0)
                ->all();

            $combinations = $this->cartesianProduct($selections);

            $existingBySignature = $product->variants()
                ->with('values')
                ->get()
                ->keyBy(fn ($variant) => $variant->values
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->sort()
                    ->values()
                    ->implode('-'));

            $seenSignatures = [];
            foreach ($combinations as $variantValueIds) {
                $signature = collect($variantValueIds)->sort()->implode('-');

                if (isset($seenSignatures[$signature])) {
                    continue;
                }
                $seenSignatures[$signature] = true;

                $variant = $existingBySignature->get($signature);

                if ($variant) {
                    // Existing combination — SKU is a stable identifier once assigned,
                    // never regenerate it here. Only stock/price change, and only if
                    // they've actually drifted (avoids a no-op write bumping updated_at).
                    $variant->fill([
                        'additional_price' => $variant->additional_price,
                        'stock' => $variant->stock,
                    ]);

                    if ($variant->isDirty()) {
                        $variant->save();
                    }
                } else {
                    // Genuinely new combination — generate a fresh SKU once.
                    $variant = $product->variants()->create([
                        'sku' => $this->skuGenerator->generate($product, $variantValueIds),
                        'additional_price' => 0,
                        'stock' => 0,
                    ]);
                }

                $variant->values()->sync($variantValueIds);
            }
        });
    }
    private function cartesianProduct(array $selections): array
    {
        $groups = array_values(array_filter($selections, fn ($ids) => count($ids) > 0));
        if (! $groups) {
            return [];
        }

        return collect($groups)->reduce(
            fn ($carry, $ids) => collect($carry)->crossJoin($ids)->map(fn ($pair) => array_merge(
                is_array($pair[0]) ? $pair[0] : [$pair[0]],
                [$pair[1]]
            ))->all(),
            [[]]
        );
    }
}
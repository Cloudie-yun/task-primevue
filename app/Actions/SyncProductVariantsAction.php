<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductVariantSkuGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

            $combinations = $this->cartesianProduct($selections);

            $seenSignatures = [];
            foreach ($combinations as $variantValueIds) {
                $this->syncOne($product, $variantValueIds, $seenSignatures);
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

    private function syncOne(Product $product, array $variantValueIds, array &$seenSignatures): void
    {
        $signature = collect($variantValueIds)->sort()->implode('-');

        if (isset($seenSignatures[$signature])) {
            return; // cartesian product can't actually produce duplicates, but stay defensive
        }
        $seenSignatures[$signature] = true;

        $variant = $product->variants()
            ->whereHas('values', fn ($q) => $q->whereIn('variant_value_id', $variantValueIds), '=', count($variantValueIds))
            ->get()
            ->first(fn ($v) => $v->values->pluck('id')->sort()->values()->all() === collect($variantValueIds)->sort()->values()->all());

        $attributes = [
            'sku' => $this->skuGenerator->resolve($product, '', $variantValueIds, $variant),
            'additional_price' => $variant->additional_price ?? 0,
            'stock' => $variant->stock ?? 0,
        ];

        $variant ? $variant->update($attributes) : $variant = $product->variants()->create($attributes);

        $variant->values()->sync($variantValueIds);
    }
}
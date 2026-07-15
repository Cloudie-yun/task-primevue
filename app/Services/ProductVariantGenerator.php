<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductVariantGenerator
{
    public function generate(Product $product, array $selections): int
    {
        $combinations = collect(array_values($selections))
            ->reduce(function ($carry, $valueIds) {
                return collect($carry)
                    ->crossJoin($valueIds)
                    ->map(fn ($pair) => array_merge(
                        is_array($pair[0]) ? $pair[0] : [$pair[0]],
                        [$pair[1]]
                    ))
                    ->all();
            }, [[]]);

        $existingSignatures = $product->variants()
            ->with('values:id')
            ->get()
            ->map(fn (ProductVariant $variant) => $variant->values->pluck('id')->sort()->implode('-'))
            ->flip();

        $created = 0;

        DB::transaction(function () use ($combinations, $existingSignatures, $product, &$created) {
            foreach ($combinations as $valueIds) {
                $signature = collect($valueIds)->sort()->implode('-');

                if (isset($existingSignatures[$signature])) {
                    continue;
                }

                $values = VariantValue::whereIn('id', $valueIds)->get();

                $variant = $product->variants()->create([
                    'sku' => $this->generateSku($product, $values),
                    'additional_price' => 0,
                    'stock' => 0,
                ]);

                $variant->values()->attach($valueIds);
                $created++;
            }
        });

        return $created;
    }

    private function generateSku(Product $product, $values): string
    {
        $base = Str::upper(Str::slug($product->slug, ''));
        $valuePart = $values->map(fn ($v) => Str::upper(Str::slug($v->value, '')))->implode('-');
        $sku = "{$base}-{$valuePart}";

        $suffix = 1;
        $original = $sku;
        while (ProductVariant::where('sku', $sku)->exists()) {
            $sku = "{$original}-{$suffix}";
            $suffix++;
        }

        return $sku;
    }
}
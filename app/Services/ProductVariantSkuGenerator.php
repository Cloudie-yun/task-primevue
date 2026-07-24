<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantValue;
use Illuminate\Support\Str;


class ProductVariantSkuGenerator
{
    public function resolve(Product $product, string $sku, array $variantValueIds, ?ProductVariant $existingVariant = null): string
    {
        $candidate = trim($sku) !== '' ? trim($sku) : $this->generate($product, $variantValueIds);

        if ($existingVariant && $existingVariant->sku === $candidate) {
            return $candidate;
        }

        return $this->ensureUnique($candidate, $existingVariant?->id);
    }

    public function generate(Product $product, array $variantValueIds): string
    {
        $values = VariantValue::whereIn('id', $variantValueIds)
            ->orderBy('variant_type_id')
            ->orderBy('id')
            ->get();

        $base = collect(preg_split('/\s+/', trim($product->name)))
            ->map(fn ($word) => preg_replace('/[^A-Za-z0-9]/', '', $word)) // strip punctuation first
            ->filter() // now drops empty strings left by pure-punctuation "words" too
            ->take(6)
            ->map(fn ($word) => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');

        $valuePart = $values->map(fn ($v) => Str::upper(Str::slug($v->value, '')))->implode('-');

        return $this->ensureUnique("{$base}-{$valuePart}");
    }

    private function ensureUnique(string $sku, ?int $excludingId = null): string
    {
        $candidate = $sku;
        $suffix = 1;

        while (ProductVariant::where('sku', $candidate)->where('id', '!=', $excludingId ?? 0)->exists()) {
            $candidate = "{$sku}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }
}
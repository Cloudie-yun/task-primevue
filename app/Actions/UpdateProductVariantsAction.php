<?php

namespace App\Actions;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class UpdateProductVariantsAction
{
    public function handle(Product $product, array $variants, array $deletedVariantIds): void
    {
        DB::transaction(function () use ($product, $variants, $deletedVariantIds) {
            if (!empty($deletedVariantIds)) {
                $product->variants()->whereIn('id', $deletedVariantIds)->delete();
            }

            foreach ($variants as $variant) {
                $product->variants()
                    ->whereKey($variant['id'])
                    ->update([
                        'additional_price' => $variant['additional_price'],
                        'stock' => $variant['stock'],
                    ]);
            }
        });
    }
}
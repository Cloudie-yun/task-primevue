<?php

namespace App\Actions;

use App\Models\Product;

class SyncProductAttributesAction
{
    public function handle(Product $product, array $attributeValueIds): void
    {
        $product->attributeValues()->sync($attributeValueIds);
    }
}
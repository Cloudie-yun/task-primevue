<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariantValue extends Model
{
    protected $fillable = [
        'product_variant_id',
        'variant_value_id',
    ];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function variantValue(): BelongsTo
    {
        return $this->belongsTo(VariantValue::class);
    }
}

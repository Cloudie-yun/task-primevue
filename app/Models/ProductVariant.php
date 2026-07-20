<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'additional_price',
        'stock',
    ];

    protected function casts(): array
    {
        return [
            'additional_price' => 'decimal:2',
            'stock' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }


    public function values(): BelongsToMany
    {
        return $this->belongsToMany(VariantValue::class, 'product_variant_values')
            ->using(ProductVariantValue::class)
            ->withTimestamps();
    }
}

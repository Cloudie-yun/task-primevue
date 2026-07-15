<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VariantValue extends Model
{
    protected $fillable = [
        'variant_type_id',
        'value',
    ];


    public function type(): BelongsTo
    {
        return $this->belongsTo(VariantType::class, 'variant_type_id');
    }


    public function productVariants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'product_variant_values')
            ->using(ProductVariantValue::class)
            ->withTimestamps();
    }
}

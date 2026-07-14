<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariantValue extends Model
{
    protected $fillable = [
        'variant_type_id',
        'value',
    ];


    public function type()
    {
        return $this->belongsTo(VariantType::class);
    }


    public function productVariants()
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'product_variant_values'
        );
    }
}

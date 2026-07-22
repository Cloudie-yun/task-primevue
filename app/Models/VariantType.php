<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VariantType extends Model
{
    protected $fillable = [
        'name',
        'generates_variants',
    ];

    protected $casts = ['generates_variants' => 'boolean'];

    public function values(): HasMany
    {
        return $this->hasMany(VariantValue::class)->orderBy('sort_order');
    }
}

<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'price' => 'decimal:2',
        ];
    }

    // Product belongs to one category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }


    // Product has many SKU variants
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }


    // Spatie image collection
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('products')
            ->singleFile();
    }
}

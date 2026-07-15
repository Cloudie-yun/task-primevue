<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'name',
        'category_id',
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
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }


    // Product has many SKU variants
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    // Spatie image collection
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('image')
            ->singleFile();
    }
}

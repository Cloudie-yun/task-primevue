<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
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

    
    public function generateImageFileName(\Illuminate\Http\UploadedFile $file, int $index = 0): string
    {
        $extension = $file->getClientOriginalExtension();
        $base = Str::slug($this->slug); // reuse the product's own slug for consistency

        $suffix = $index > 0 ? "-{$index}" : '';

        return "{$base}-{$this->id}{$suffix}.{$extension}";
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

    // Product Value Pivot
    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(VariantValue::class, 'product_attribute_value')->withTimestamps();
    }

    // Spatie image collection
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }
}

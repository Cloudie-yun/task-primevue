<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductImageController extends Controller
{
    public function destroy(Product $product, Media $media)
    {
        abort_unless($media->model_id === $product->id, 404);

        // Prevent deleting the last remaining image
        if ($product->getMedia('images')->count() <= 1) {
            return back()->with('error', 'A product must have at least one image.');
        }

        $media->delete();

        return back()->with('success', 'Image removed.');
    }
}

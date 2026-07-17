<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Product\CategoryController;
use App\Http\Controllers\Product\VariantTypeController;
use App\Http\Controllers\Product\VariantValueController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Product\ProductVariantController;
use App\Http\Controllers\Product\ProductImageController;

Route::middleware(['auth', 'verified'])
    ->prefix('product-settings')
    ->name('product-settings.')
    ->group(function () {

        Route::get('/', function () {
            return redirect()
                ->route('product-settings.categories');
        })->name('index');

        //Category CRUD
        Route::get('/categories', [CategoryController::class, 'index'])
            ->name('categories');
        Route::post('/categories', [CategoryController::class, 'store'])
            ->name('categories.store');
        Route::patch('/categories/{category}', [CategoryController::class, 'update'])
            ->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
            ->name('categories.destroy');

        //Variant Type CRUD
        Route::get('/variants', [VariantTypeController::class, 'index'])
            ->name('variants');
        Route::post('/variants', [VariantTypeController::class, 'store'])
            ->name('variants.store');
        Route::patch('/variants/{variantType}', [VariantTypeController::class, 'update'])
            ->name('variants.update');
        Route::delete('/variants/{variantType}', [VariantTypeController::class, 'destroy'])
            ->name('variants.destroy');
        
        // Variant Values CRUD
        Route::post('/variants/{variantType}/values', [VariantValueController::class, 'store'])
            ->name('variant-values.store');            
        Route::patch('/variant-values/{variantValue}', [VariantValueController::class, 'update'])
            ->name('variant-values.update');
        Route::delete('/variant-values/{variantValue}', [VariantValueController::class, 'destroy'])
            ->name('variant-values.destroy');
    });

Route::middleware(['auth', 'verified'])
    ->prefix('products')
    ->name('products.')
    ->group(function () {

        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/create', [ProductController::class, 'create'])->name('create');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
        Route::patch('/{product}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy');

        Route::delete('/{product}/images/{media}', [ProductImageController::class, 'destroy'])->name('images.destroy')->scopeBindings();

        Route::post('/{product}/variants/generate', [ProductVariantController::class, 'generate'])->name('variants.generate');
        Route::patch('/{product}/variants/{variant}', [ProductVariantController::class, 'update'])->name('variants.update')->scopeBindings();
        Route::delete('/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])->name('variants.destroy')->scopeBindings();
    });


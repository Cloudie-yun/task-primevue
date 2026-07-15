<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Product\CategoryController;
use App\Http\Controllers\Product\VariantTypeController;
use App\Http\Controllers\Product\VariantValueController;

Route::middleware(['auth', 'verified'])
    ->prefix('product-settings')
    ->name('product-settings.')
    ->group(function () {

        Route::get('/', function () {
            return redirect()
                ->route('product-settings.categories');
        })->name('index');


        Route::get('/categories', [CategoryController::class, 'index'])
            ->name('categories');
        Route::post('/categories', [CategoryController::class, 'store'])
            ->name('categories.store');
        Route::patch('/categories/{category}', [CategoryController::class, 'update'])
            ->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
            ->name('categories.destroy');

        Route::get('/variants', [VariantTypeController::class, 'index'])
            ->name('variants');
        Route::post('/variants', [VariantTypeController::class, 'store'])
            ->name('variants.store');
        Route::patch('/variants/{variantType}', [VariantTypeController::class, 'update'])
            ->name('variants.update');
        Route::delete('/variants/{variantType}', [VariantTypeController::class, 'destroy'])
            ->name('variants.destroy');

        Route::patch('/variant-values/{variantValue}', [VariantValueController::class, 'update'])
            ->name('variant-values.update');
        Route::delete('/variant-values/{variantValue}', [VariantValueController::class, 'destroy'])
            ->name('variant-values.destroy');

    });
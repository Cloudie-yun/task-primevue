<?php

use App\Models\Product;
use App\Models\User;
use Inertia\Testing\Assert;

it('renders the dedicated product variants page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $product = Product::create([
        'name' => 'Test Product',
        'slug' => 'test-product',
        'description' => 'A test product',
        'price' => '19.99',
        'status' => 'draft',
    ]);

    $this->actingAs($user)
        ->get(route('products.variants.index', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('products/VariantsPage')
            ->where('product.id', $product->id));
});

<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\VariantType;
use App\Models\VariantValue;
use Illuminate\Http\UploadedFile;

it('stores product variants from the form payload', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $category = Category::create([
        'name' => 'Test Category',
        'description' => 'A category for tests',
        'is_active' => true,
    ]);

    $type = VariantType::create(['name' => 'Color']);
    $value = VariantValue::create([
        'variant_type_id' => $type->id,
        'value' => 'Red',
    ]);

    $response = $this->actingAs($user)
        ->post(route('products.store'), [
            'name' => 'Persisted Variant Product',
            'category_id' => $category->id,
            'description' => 'A product with variants',
            'price' => '29.99',
            'status' => 'draft',
            'images' => [UploadedFile::fake()->image('product.jpg')],
            'variants' => [[
                'sku' => 'PVP-RED',
                'additional_price' => '8.50',
                'stock' => 12,
                'values' => [[
                    'id' => $value->id,
                    'value' => 'Red',
                    'variant_type_id' => $type->id,
                ]],
            ]],
        ]);

    $response->assertRedirect();

    $product = Product::where('name', 'Persisted Variant Product')->firstOrFail();

    expect($product->variants()->count())->toBe(1)
        ->and($product->variants()->first()->sku)->toBe('PVP-RED')
        ->and($product->variants()->first()->values()->pluck('variant_values.id')->all())->toEqual([$value->id]);
});

<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\ProductStatus;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{

    public function definition(): array
    {
        $name = ucwords($this->faker->unique()->words(3, true));

        return [
            'name' => $name,
            'category_id' => Category::query()->inRandomOrder()->first()?->id,
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(12),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'status' => $this->faker->randomElement(ProductStatus::cases())->value,
        ];
    }
}

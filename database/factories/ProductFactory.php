<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            // Stored as cents: 4999 = 49.99
            'price' => fake()->numberBetween(500, 200000),

            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(3, true),

            'image' => fake()->boolean(80)
                ? 'products/' . fake()->uuid() . '.jpg'
                : null,

            'is_active' => fake()->boolean(95),
        ];
    }
}
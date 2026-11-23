<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => fake()->unique()->ean8(),
            'name' => fake()->unique()->words(2, true),
            'unit' => 'pcs',
            'price' => fake()->numberBetween(1, 100) * 500,
            'min_stock' => 5,
        ];
    }
}

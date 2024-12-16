<?php

namespace Xoshbin\JmeryarAccounting\Database\Factories;

use Xoshbin\JmeryarAccounting\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'sku' => $this->faker->unique()->word,
            'description' => $this->faker->sentence,
            'category_id' => ProductCategory::inRandomOrder()->first()->id
        ];
    }
}

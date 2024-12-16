<?php

namespace Xoshbin\JmeryarAccounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tax>
 */
class TaxFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'tax_computation' => $this->faker->randomElement(['Fixed', 'Percentage', 'Group', 'Percentage_inclusive']),
            'amount' => 1,
            'type' => $this->faker->randomElement(['Sales', 'Purchases', 'None']),
            'tax_scope' => $this->faker->randomElement(['Goods', 'Services']),
            'status' => $this->faker->randomElement(['Active', 'Inactive']),
        ];
    }
}

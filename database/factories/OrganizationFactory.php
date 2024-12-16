<?php

namespace Xoshbin\JmeryarAccounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'country' => fake()->country(),
            'website' => fake()->url(),
            'logo' => fake()->imageUrl(),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['Active', 'Inactive']),
            'industry' => fake()->randomElement(['Tech', 'Finance', 'Health', 'Education', 'Other']),
            'size' => fake()->randomElement(['1-10', '11-50', '51-200', '201-500', '500+']),
        ];
    }
}

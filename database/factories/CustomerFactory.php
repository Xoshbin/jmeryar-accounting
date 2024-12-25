<?php

namespace Xoshbin\JmeryarAccounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Xoshbin\JmeryarAccounting\Models\Customer;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Xoshbin\JmeryarAccounting\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
        ];
    }
}

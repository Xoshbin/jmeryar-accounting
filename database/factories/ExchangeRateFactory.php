<?php

namespace Xoshbin\JmeryarAccounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Xoshbin\JmeryarAccounting\Models\Currency;
use Xoshbin\JmeryarAccounting\Models\ExchangeRate;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Xoshbin\JmeryarAccounting\Models\ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    protected $model = ExchangeRate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'base_currency_id' => Currency::inRandomOrder()->first()->id,
            'target_currency_id' => Currency::inRandomOrder()->first()->id,
            'rate' => $this->faker->numberBetween(8, 18),
            'created_at' => $this->faker->date(),
            'updated_at' => $this->faker->date(),
        ];
    }
}

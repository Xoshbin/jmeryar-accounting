<?php

namespace Xoshbin\JmeryarAccounting\Database\Factories;

use Xoshbin\JmeryarAccounting\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Xoshbin\JmeryarAccounting\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => 0,
            'payment_date' => Carbon::now(),
            'payment_type' => Payment::TYPE_INCOME,
            'payment_method' => 'Cash',
            'note' => 'Initial payment for seeding'
        ];
    }
}

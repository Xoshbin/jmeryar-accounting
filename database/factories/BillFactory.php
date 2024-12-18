<?php

namespace Xoshbin\JmeryarAccounting\Database\Factories;

use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\Bill;
use Xoshbin\JmeryarAccounting\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Xoshbin\JmeryarAccounting\Models\Bill>
 */
class BillFactory extends Factory
{
    protected $model = Bill::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bill_number' => $this->faker->unique()->numerify('BILL-#####'),
            'bill_date' => $this->faker->date(),
            'supplier_id' => Supplier::inRandomOrder()->first()->id,
            'total_amount' => 0, // will be updated after items are added
            'total_paid_amount' => 0, // will be updated after items are added
            'amount_due' => 0, // will be updated after items are added
            'status' => $this->faker->randomElement(['Draft', 'Received']),
            'expense_account_id' => Account::where('type', Account::TYPE_EXPENSE)->first()->id,
            'liability_account_id' => Account::where('type', Account::TYPE_LIABILITY)->first()->id,
            'currency_id' => 2
        ];
    }
}
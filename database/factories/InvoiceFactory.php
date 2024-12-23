<?php

namespace Xoshbin\JmeryarAccounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\Customer;
use Xoshbin\JmeryarAccounting\Models\Invoice;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Xoshbin\JmeryarAccounting\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_number' => $this->faker->unique()->numerify('INV-#####'),
            'invoice_date' => $this->faker->date(),
            'customer_id' => Customer::inRandomOrder()->first()->id,
            'total_amount' => 0, // will be updated after items are added
            'total_paid_amount' => 0, // will be updated after items are added
            'amount_due' => 0, // will be updated after items are added
            'status' => $this->faker->randomElement(['Draft', 'Sent']),
            'revenue_account_id' => Account::where('type', Account::TYPE_REVENUE)->first()->id,
            'inventory_account_id' => Account::where('type', Account::TYPE_ASSET)->first()->id,
            'currency_id' => 2,
            'tax_amount' => 0,
        ];
    }
}

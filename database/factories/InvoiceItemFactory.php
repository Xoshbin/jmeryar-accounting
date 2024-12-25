<?php

namespace Xoshbin\JmeryarAccounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Xoshbin\JmeryarAccounting\Models\Invoice;
use Xoshbin\JmeryarAccounting\Models\InvoiceItem;
use Xoshbin\JmeryarAccounting\Models\Product;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Xoshbin\JmeryarAccounting\Models\InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = $this->faker->numberBetween(1000, 10000);
        $quantity = $this->faker->numberBetween(1, 100);

        return [
            'invoice_id' => Invoice::factory(),
            'product_id' => Product::inRandomOrder()->first()->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $quantity,
        ];
    }
}

<?php

namespace Xoshbin\JmeryarAccounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Xoshbin\JmeryarAccounting\Models\Bill;
use Xoshbin\JmeryarAccounting\Models\BillItem;
use Xoshbin\JmeryarAccounting\Models\Product;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Xoshbin\JmeryarAccounting\Models\BillItem>
 */
class BillItemFactory extends Factory
{
    protected $model = BillItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $costPrice = $this->faker->numberBetween(1000, 10000);
        $quantity = $this->faker->numberBetween(1, 100);

        return [
            'bill_id' => Bill::factory(),
            'product_id' => Product::inRandomOrder()->first()->id,
            'quantity' => $quantity, // FOR FUTURE:: don't get confused again, the number maybe 0 in the database table after seed run, because when invoice seeder run it decreases the quantity
            'cost_price' => $costPrice,
            'unit_price' => $costPrice * 5, // Add %5 profit
            'total_cost' => $costPrice * $quantity,
        ];
    }
}

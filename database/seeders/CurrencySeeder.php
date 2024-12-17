<?php

namespace Xoshbin\JmeryarAccounting\Database\Seeders;

use Xoshbin\JmeryarAccounting\Models\Currency;
use Xoshbin\JmeryarAccounting\Models\ExchangeRate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'United States Dollar', 'symbol' => '$', 'currency_unit' => 'Dollar', 'currency_subunit' => 'Cent', 'status' => 'Active'],
            ['code' => 'IQD', 'name' => 'Iraqi Dinar', 'symbol' => 'ع.د', 'currency_unit' => 'Dinar', 'currency_subunit' => 'Fils', 'status' => 'Active'],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'currency_unit' => 'Euro', 'currency_subunit' => 'Cent', 'status' => 'Active'],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'currency_unit' => 'Pound', 'currency_subunit' => 'Penny', 'status' => 'Active'],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'currency_unit' => 'Yen', 'currency_subunit' => 'Sen', 'status' => 'Active'],
            ['code' => 'KWD', 'name' => 'Kuwaiti Dinar', 'symbol' => 'KD', 'currency_unit' => 'Dinar', 'currency_subunit' => 'Fils', 'status' => 'Active'],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹', 'currency_unit' => 'Rupee', 'currency_subunit' => 'Paise', 'status' => 'Active'],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥', 'currency_unit' => 'Yuan', 'currency_subunit' => 'Fen', 'status' => 'Active'],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$', 'currency_unit' => 'Dollar', 'currency_subunit' => 'Cent', 'status' => 'Active'],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'C$', 'currency_unit' => 'Dollar', 'currency_subunit' => 'Cent', 'status' => 'Active'],
            ['code' => 'ZAR', 'name' => 'South African Rand', 'symbol' => 'R', 'currency_unit' => 'Rand', 'currency_subunit' => 'Cent', 'status' => 'Active'],
        ];

        Currency::insert($currencies);
    }
}

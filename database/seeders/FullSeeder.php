<?php

namespace Xoshbin\JmeryarAccounting\Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FullSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AccountSeeder::class,
            ProductCategorySeeder::class,
            ProductSeeder::class,
            CurrencySeeder::class,
            ExchangeRateSeeder::class,
            TaxSeeder::class,
            SettingSeeder::class,
            CustomerSeeder::class,
            SupplierSeeder::class,
            BillSeeder::class,
            InvoiceSeeder::class,
        ]);
    }
}

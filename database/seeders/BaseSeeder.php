<?php

namespace Xoshbin\JmeryarAccounting\Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AccountSeeder::class,
            ProductCategorySeeder::class,
            CurrencySeeder::class,
            TaxSeeder::class,
        ]);
    }
}

<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JmeryarSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * sail artisan migrate:reset && sail artisan migrate && sail artisan db:seed --class=JmeryarSeeder
     */
    public function run(): void
    {
        $this->call([
            AccountSeeder::class,
            ProductCategorySeeder::class,
            CurrencySeeder::class,
            TaxSeeder::class,
            SettingSeeder::class,
        ]);
    }
}

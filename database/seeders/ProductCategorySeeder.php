<?php

namespace Xoshbin\JmeryarAccounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Xoshbin\JmeryarAccounting\Models\ProductCategory;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productCategories = [
            ['name' => 'Electronics', 'description' => 'Electronic devices and accessories'],
            ['name' => 'Office Supplies', 'description' => 'Stationery and office supplies'],
            ['name' => 'Furniture', 'description' => 'Office and home furniture'],
        ];

        ProductCategory::insert($productCategories);
    }
}

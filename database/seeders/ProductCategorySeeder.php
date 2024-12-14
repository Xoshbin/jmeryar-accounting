<?php

namespace Database\Seeders;

use Xoshbin\JmeryarAccounting\Models\ProductCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
            ['name' => 'Furniture', 'description' => 'Office and home furniture']
        ];

        ProductCategory::insert($productCategories);
    }
}

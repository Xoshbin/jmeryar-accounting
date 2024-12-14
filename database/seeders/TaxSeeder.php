<?php

namespace Database\Seeders;

use Xoshbin\JmeryarAccounting\Models\Tax;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxes = [
            [
                'name' => '15% Sales',
                'tax_computation' => 'Percentage',
                'amount' => 15,
                'type' => 'Sales',
                'tax_scope' => 'Goods',
                'status' => 'Active',
            ],
            [
                'name' => '5% Sales',
                'tax_computation' => 'Percentage',
                'amount' => 5,
                'type' => 'Sales',
                'tax_scope' => 'Goods',
                'status' => 'Active',
            ],
            [
                'name' => '5% Sales Services',
                'tax_computation' => 'Percentage',
                'amount' => 5,
                'type' => 'Sales',
                'tax_scope' => 'Services',
                'status' => 'Active',
            ],
            [
                'name' => '5% Purchases',
                'tax_computation' => 'Percentage',
                'amount' => 5,
                'type' => 'Purchases',
                'tax_scope' => 'Goods',
                'status' => 'Active',
            ],
            [
                'name' => 'Fixed 5000',
                'tax_computation' => 'Fixed',
                'amount' => 5000,
                'type' => 'Sales',
                'tax_scope' => 'Goods',
                'status' => 'Active',
            ],
            [
                'name' => '20%',
                'tax_computation' => 'Percentage',
                'amount' => 20,
                'type' => 'Sales',
                'tax_scope' => 'Goods',
                'status' => 'Inactive',
            ],
        ];

        Tax::insert($taxes);
    }
}

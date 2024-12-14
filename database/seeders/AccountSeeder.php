<?php

namespace Database\Seeders;

use Xoshbin\JmeryarAccounting\Models\Account;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            // Assets
            ['name' => 'Cash', 'type' => 'Asset', 'code' => '1000', 'parent_id' => null],
            ['name' => 'Accounts Receivable', 'type' => 'Asset', 'code' => '1100', 'parent_id' => null],
            ['name' => 'Inventory', 'type' => 'Asset', 'code' => '1200', 'parent_id' => null],
            ['name' => 'Prepaid Expenses', 'type' => 'Asset', 'code' => '1300', 'parent_id' => null],
            ['name' => 'Property, Plant, and Equipment', 'type' => 'Asset', 'code' => '1400', 'parent_id' => null],
            ['name' => 'Accumulated Depreciation', 'type' => 'Asset', 'code' => '1450', 'parent_id' => 5], // Child of PPE
            ['name' => 'Tax Receivable', 'type' => 'Asset', 'code' => '1500', 'parent_id' => null],
            ['name' => 'Tax Paid', 'type' => 'Asset', 'code' => '1600', 'parent_id' => null],

            // Liabilities
            ['name' => 'Accounts Payable', 'type' => 'Liability', 'code' => '2000', 'parent_id' => null],
            ['name' => 'Short-Term Loans', 'type' => 'Liability', 'code' => '2100', 'parent_id' => null],
            ['name' => 'Accrued Expenses', 'type' => 'Liability', 'code' => '2200', 'parent_id' => null],
            ['name' => 'Long-Term Debt', 'type' => 'Liability', 'code' => '2300', 'parent_id' => null],
            ['name' => 'Tax Payable', 'type' => 'Liability', 'code' => '2400', 'parent_id' => null],
            ['name' => 'Tax Received', 'type' => 'Liability', 'code' => '2500', 'parent_id' => null],

            // Equity
            ['name' => 'Common Stock', 'type' => 'Equity', 'code' => '3000', 'parent_id' => null],
            ['name' => 'Retained Earnings', 'type' => 'Equity', 'code' => '3100', 'parent_id' => null],
            ['name' => 'Dividends', 'type' => 'Equity', 'code' => '3200', 'parent_id' => null],

            // Revenue
            ['name' => 'Sales Revenue', 'type' => 'Revenue', 'code' => '4000', 'parent_id' => null],
            ['name' => 'Service Revenue', 'type' => 'Revenue', 'code' => '4100', 'parent_id' => null],
            ['name' => 'Interest Income', 'type' => 'Revenue', 'code' => '4200', 'parent_id' => null],
            ['name' => 'Tax Revenue', 'type' => 'Revenue', 'code' => '4300', 'parent_id' => null],

            // Expenses
            ['name' => 'Cost of Goods Sold', 'type' => 'Expense', 'code' => '5000', 'parent_id' => null],
            ['name' => 'Salaries and Wages', 'type' => 'Expense', 'code' => '5100', 'parent_id' => null],
            ['name' => 'Rent Expense', 'type' => 'Expense', 'code' => '5200', 'parent_id' => null],
            ['name' => 'Utilities', 'type' => 'Expense', 'code' => '5300', 'parent_id' => null],
            ['name' => 'Depreciation Expense', 'type' => 'Expense', 'code' => '5400', 'parent_id' => 18], // Child of PPE Depreciation
            ['name' => 'Advertising', 'type' => 'Expense', 'code' => '5500', 'parent_id' => null],
            ['name' => 'Tax Expense', 'type' => 'Expense', 'code' => '5600', 'parent_id' => null],
        ];

        Account::insert($accounts);
    }
}

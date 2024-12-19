<?php

namespace Xoshbin\JmeryarAccounting\Database\Seeders;

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
            ['name' => 'Curent Assets', 'type' => 'Asset', 'code' => '1050', 'parent_id' => null],
            ['name' => 'Cash', 'type' => 'Asset', 'code' => '1100', 'parent_id' => null],
            ['name' => 'Bank', 'type' => 'Asset', 'code' => '1150', 'parent_id' => null],
            ['name' => 'Accounts Receivable', 'type' => 'Asset', 'code' => '1200', 'parent_id' => null],
            ['name' => 'Inventory', 'type' => 'Asset', 'code' => '1250', 'parent_id' => null],
            ['name' => 'Prepaid Expenses', 'type' => 'Asset', 'code' => '1300', 'parent_id' => null],
            // Non-Current Assets (Fixed Assets)
            ['name' => 'Equipment', 'type' => 'Asset', 'code' => '1350', 'parent_id' => null],
            ['name' => 'Property, Plant, and Equipment', 'type' => 'Asset', 'code' => '1400', 'parent_id' => null],
            ['name' => 'Accumulated Depreciation', 'type' => 'Asset', 'code' => '1450', 'parent_id' => 5], // Child of PPE
            ['name' => 'Tax Receivable', 'type' => 'Asset', 'code' => '1500', 'parent_id' => null],
            ['name' => 'Tax Paid', 'type' => 'Asset', 'code' => '1550', 'parent_id' => null],

            // Liabilities
            ['name' => 'Liabilities', 'type' => 'Liability', 'code' => '2050', 'parent_id' => null],
            ['name' => 'Current Liabilities', 'type' => 'Liability', 'code' => '2100', 'parent_id' => null],
            ['name' => 'Accounts Payable', 'type' => 'Liability', 'code' => '2150', 'parent_id' => null],
            ['name' => 'Short-Term Debt', 'type' => 'Liability', 'code' => '2200', 'parent_id' => null],
            ['name' => 'Accrued Liabilities', 'type' => 'Liability', 'code' => '2250', 'parent_id' => null],
            ['name' => 'Unearned Revenue', 'type' => 'Liability', 'code' => '2300', 'parent_id' => null],
            // Non-Current Liabilities (Long-term Liabilities)
            ['name' => 'Long-term Debt', 'type' => 'Liability', 'code' => '2350', 'parent_id' => null],
            ['name' => 'Deferred Tax Liabilities', 'type' => 'Liability', 'code' => '2400', 'parent_id' => null],
            ['name' => 'Lease Obligations', 'type' => 'Liability', 'code' => '2450', 'parent_id' => null],
            ['name' => 'Tax Payable', 'type' => 'Liability', 'code' => '2500', 'parent_id' => null],
            ['name' => 'Tax Received', 'type' => 'Liability', 'code' => '2550', 'parent_id' => null],

            // Equity
            ['name' => 'Equity', 'type' => 'Equity', 'code' => '3050', 'parent_id' => null],
            ['name' => 'Common Stock', 'type' => 'Equity', 'code' => '3100', 'parent_id' => null],
            ['name' => 'Retained Earnings', 'type' => 'Equity', 'code' => '3150', 'parent_id' => null],
            ['name' => 'Dividends', 'type' => 'Equity', 'code' => '3200', 'parent_id' => null],

            // Revenue
            ['name' => 'Sales Revenue', 'type' => 'Revenue', 'code' => '4050', 'parent_id' => null],
            ['name' => 'Service Revenue', 'type' => 'Revenue', 'code' => '4100', 'parent_id' => null],
            ['name' => 'Interest Income', 'type' => 'Revenue', 'code' => '4150', 'parent_id' => null],
            ['name' => 'Tax Revenue', 'type' => 'Revenue', 'code' => '4200', 'parent_id' => null],

            // Expenses
            ['name' => 'Expenses', 'type' => 'Expense', 'code' => '5050', 'parent_id' => null],
            ['name' => 'Cost of Goods Sold', 'type' => 'Expense', 'code' => '5100', 'parent_id' => null],
            ['name' => 'Salaries and Wages', 'type' => 'Expense', 'code' => '5150', 'parent_id' => null],
            ['name' => 'Rent Expense', 'type' => 'Expense', 'code' => '5200', 'parent_id' => null],
            ['name' => 'Utilities', 'type' => 'Expense', 'code' => '5250', 'parent_id' => null],
            ['name' => 'Depreciation Expense', 'type' => 'Expense', 'code' => '5300', 'parent_id' => 18],
            ['name' => 'Advertising', 'type' => 'Expense', 'code' => '5350', 'parent_id' => null],
            ['name' => 'Tax Expense', 'type' => 'Expense', 'code' => '5400', 'parent_id' => null],
        ];

        Account::insert($accounts);
    }
}

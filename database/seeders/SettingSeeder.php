<?php

namespace Database\Seeders;

use Xoshbin\JmeryarAccounting\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'company_name' => 'Jmeryar',
                'company_email' => 'info@jmeryar.com',
                'company_phone' => '1234567890',
                'company_address' => '123 Main St',
                'company_website' => 'jmeryar.com',
                'currency_id' => 1
            ]
        ];
        Setting::insert($settings);
    }
}

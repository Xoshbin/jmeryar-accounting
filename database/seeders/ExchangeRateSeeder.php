<?php

namespace Xoshbin\JmeryarAccounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Xoshbin\JmeryarAccounting\Models\Currency;
use Xoshbin\JmeryarAccounting\Models\ExchangeRate;

class ExchangeRateSeeder extends Seeder
{
    public function run()
    {
        $usd = Currency::where('code', 'USD')->first();
        $eur = Currency::where('code', 'EUR')->first();
        $gbp = Currency::where('code', 'GBP')->first();

        ExchangeRate::create([
            'base_currency_id' => $usd->id,
            'target_currency_id' => $eur->id,
            'rate' => 0.85,
        ]);

        ExchangeRate::create([
            'base_currency_id' => $usd->id,
            'target_currency_id' => $gbp->id,
            'rate' => 0.75,
        ]);

        ExchangeRate::create([
            'base_currency_id' => $eur->id,
            'target_currency_id' => $gbp->id,
            'rate' => 0.88,
        ]);
    }
}

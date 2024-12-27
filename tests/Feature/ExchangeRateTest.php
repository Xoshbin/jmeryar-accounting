<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Xoshbin\JmeryarAccounting\Database\Seeders\DatabaseSeeder;
use Xoshbin\JmeryarAccounting\Models\Currency;
use Xoshbin\JmeryarAccounting\Models\ExchangeRate;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('can create an exchange rate', function () {
    $USDCurrency = Currency::where('code', 'USD')->first();

    $IQDCurrency = Currency::where('code', 'IQD')->first();

    $exchangeRate = ExchangeRate::create([
        'base_currency_id' => $USDCurrency->id,
        'target_currency_id' => $IQDCurrency->id,
        'rate' => 1500,
    ]);

    expect($exchangeRate->rate)->toBe(1500);
    expect($exchangeRate->baseCurrency->code)->toBe('USD');
    expect($exchangeRate->targetCurrency->code)->toBe('IQD');

    // 1 USD = 0.0006666666666666666 IQD
    expect(1 / $exchangeRate->rate)->toBe(0.0006666666666666666);

    // 100 USD = 150,000 IQD
    expect($exchangeRate->rate * 100)->toBe(150000);
});

it('can handle USD to EUR exchange rate', function () {
    $USDCurrency = Currency::where('code', 'USD')->first();
    $EURCurrency = Currency::where('code', 'EUR')->first();

    $exchangeRate = ExchangeRate::create([
        'base_currency_id' => $USDCurrency->id,
        'target_currency_id' => $EURCurrency->id,
        'rate' => 0.96,
    ]);

    expect($exchangeRate->rate)->toBe(0.96);
    expect($exchangeRate->baseCurrency->code)->toBe('USD');
    expect($exchangeRate->targetCurrency->code)->toBe('EUR');

    // 100 USD = 96 EUR
    expect($exchangeRate->rate * 100)->toBe(96.0);
});

it('can handle USD to IRR exchange rate', function () {
    $USDCurrency = Currency::where('code', 'USD')->first();
    $IRRCurrency = Currency::where('code', 'IRR')->first();

    $exchangeRate = ExchangeRate::create([
        'base_currency_id' => $USDCurrency->id,
        'target_currency_id' => $IRRCurrency->id,
        'rate' => 42087.50,
    ]);

    expect($exchangeRate->rate)->toBe(42087.50);
    expect($exchangeRate->baseCurrency->code)->toBe('USD');
    expect($exchangeRate->targetCurrency->code)->toBe('IRR');

    // 100 USD = 4,208,750 IRR
    expect($exchangeRate->rate * 100)->toBe(4208750.0);
});

<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Forms\Components\Field;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;
use Xoshbin\JmeryarAccounting\Models\Currency;
use Xoshbin\JmeryarAccounting\Models\Setting;

class MoneyInput extends TextInput
{
    protected string | Closure | null $currencyCode = null;

    protected string $defaultCurrencyCode = 'USD'; // Default currency code

    protected function setUp(): void
    {
        parent::setUp();

        // Dynamically set the prefix based on the currency symbol
        $this->prefix(function () {
            return $this->getCurrencySymbol();
        });

        $this->numeric();

        // The mask (Decimal) is preventing calculations, disabled for now.
        // $this->mask(function () {
        //     return RawJs::make(
        //         strtr(
        //             '$money($input, \'{decimalSeparator}\', \'{groupingSeparator}\', {fractionDigits})',
        //             [
        //                 '{decimalSeparator}' => '.',
        //                 '{groupingSeparator}' => ',',
        //                 '{fractionDigits}' => 2,
        //             ]
        //         )
        //     );
        // });
    }

    public function currencyCode(string | Closure | null $currencyCode): static
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    protected function getCurrencySymbol(): string
    {
        // Step 1: Evaluate the currency code (supports Closure)
        $code = $this->evaluate($this->currencyCode);

        // Step 2: Fallback to the currency code from settings if not explicitly set
        if (! $code) {
            $code = Setting::first()?->currency->code;
        }

        // Step 3: Fallback to default currency code if no settings found
        $code = $code ?? $this->defaultCurrencyCode;

        // Fetch the currency symbol from the database
        $currency = Currency::where('code', $code)->first();

        // Return the symbol or default to '$' if not found
        return $currency?->symbol ?? '$';
    }
}

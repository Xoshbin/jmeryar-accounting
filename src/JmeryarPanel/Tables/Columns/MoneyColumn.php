<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Tables\Columns;

use Filament\Tables\Columns\Column;

use Closure;
use Filament\Tables\Columns\TextColumn;
use Xoshbin\JmeryarAccounting\Models\Currency;
use Xoshbin\JmeryarAccounting\Models\Setting;

class MoneyColumn extends TextColumn
{
    protected string|Closure|null $currencyCode = null;

    protected string $defaultCurrencyCode = 'USD'; // Default currency code

    public function currencyCode(string|Closure|null $currencyCode): static
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    protected function getCurrency(): string
    {
        // Step 1: Evaluate the currency code (supports Closure)
        $code = $this->evaluate($this->currencyCode);

        // Step 2: Fallback to the currency code from settings if not explicitly provided
        if (!$code) {
            $code = Setting::first()?->currency->code;
        }

        // Step 3: Use default currency code if no settings are found
        return $code ?? $this->defaultCurrencyCode;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Use Filament's currency formatting
        $this->money(fn() => $this->getCurrency()); // Dynamically sets the currency
    }
}

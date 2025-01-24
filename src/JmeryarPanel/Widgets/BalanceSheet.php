<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Widgets;

use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\ExchangeRate;
use Xoshbin\JmeryarAccounting\Models\Setting;
use Xoshbin\JmeryarAccounting\Services\Calculator;

class BalanceSheet extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $defaultCurrecny = Setting::first()?->currency->code;

        $startDate = !is_null($this->filters['startDate'] ?? null)
            ? Carbon::parse($this->filters['startDate'])->startOfDay()
            : null;

        $endDate = !is_null($this->filters['endDate'] ?? null)
            ? Carbon::parse($this->filters['endDate'])->endOfDay()
            : now()->endOfDay();

        // Calculate Total Balances
        $totalAssets = Calculator::calculateBalance('Asset', $startDate, $endDate);
        $totalLiabilities = Calculator::calculateBalance('Liability', $startDate, $endDate);
        $totalEquity = Calculator::calculateBalance('Equity', $startDate, $endDate);
        $totalRevenues = Calculator::calculateBalance('Revenue', $startDate, $endDate);
        $totalExpenses = Calculator::calculateBalance('Expense', $startDate, $endDate);

        // Profit/Loss = Revenues - Expenses
        $profitOrLoss = $totalRevenues - $totalExpenses;

        // Adjust Equity to include Profit/Loss
        $totalEquity += $profitOrLoss;

        // Return Stats
        return [
            Stat::make('Assets', Number::currency($totalAssets, $defaultCurrecny)),
            Stat::make('Liabilities', Number::currency($totalLiabilities, $defaultCurrecny)),
            Stat::make('Equity', Number::currency($totalEquity, $defaultCurrecny)),
        ];
    }
}

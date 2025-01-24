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

class IncomeStatement extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $defaultCurrecny = Setting::first()?->currency->code;

        $startDate = ! is_null($this->filters['startDate'] ?? null)
            ? Carbon::parse($this->filters['startDate'])->startOfDay()
            : null;

        $endDate = ! is_null($this->filters['endDate'] ?? null)
            ? Carbon::parse($this->filters['endDate'])->endOfDay()
            : now()->endOfDay();

        // Calculate Revenues
        $revenues = Calculator::calculateBalance('Revenue', $startDate, $endDate);

        // Calculate Expenses
        $expenses = Calculator::calculateBalance('Expense', $startDate, $endDate);

        // Calculate Net Profit
        $netProfit = $revenues - $expenses;

        // Return Stats
        return [
            Stat::make('Revenues', Number::currency($revenues, $defaultCurrecny)),
            Stat::make('Expenses', Number::currency($expenses, $defaultCurrecny)),
            Stat::make('Profit', Number::currency($netProfit, $defaultCurrecny)),
        ];
    }
}

<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Widgets;

use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\Setting;

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
        $revenues = $this->calculateBalance('Revenue', $startDate, $endDate);

        // Calculate Expenses
        $expenses = $this->calculateBalance('Expense', $startDate, $endDate);

        // Calculate Net Profit
        $netProfit = $revenues - $expenses;

        // Return Stats
        return [
            Stat::make('Revenues', Number::currency($revenues, $defaultCurrecny)),
            Stat::make('Expenses', Number::currency($expenses, $defaultCurrecny)),
            Stat::make('Profit', Number::currency($netProfit, $defaultCurrecny)),
        ];
    }

    protected function calculateBalance($accountType, $startDate, $endDate): int
    {
        return Account::where('type', $accountType)
            ->whereHas('journalEntries', function ($query) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }
            })
            ->get()
            ->sum(function ($account) use ($startDate, $endDate) {
                $debits = $account->journalEntries
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('debit');
                $credits = $account->journalEntries
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('credit');

                return in_array($account->type, ['Asset', 'Expense'])
                    ? $debits - $credits
                    : $credits - $debits;
            });
    }
}

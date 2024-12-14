<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Widgets;

use Xoshbin\JmeryarAccounting\Models\JournalEntry;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class IncomeStatement extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $startDate = !is_null($this->filters['startDate'] ?? null)
            ? Carbon::parse($this->filters['startDate'])
            : null;

        $endDate = !is_null($this->filters['endDate'] ?? null)
            ? Carbon::parse($this->filters['endDate'])
            : now();

        // Calculate Revenues
        $revenues = JournalEntry::whereHas('account', function ($query) {
            $query->where('type', 'Revenue');
        })
            ->when($startDate, fn($query) => $query->whereBetween('created_at', [$startDate, $endDate]))
            ->sum('credit'); // Sum of credits for revenue accounts

        // Calculate Expenses
        $expenses = JournalEntry::whereHas('account', function ($query) {
            $query->where('type', 'Expense');
        })
            ->when($startDate, fn($query) => $query->whereBetween('created_at', [$startDate, $endDate]))
            ->sum('debit'); // Sum of debits for expense accounts

        // Calculate Net Profit
        $netProfit = $revenues - $expenses;

        // Return Stats
        return [
            Stat::make('Revenues', $revenues / 100),
            Stat::make('Expenses', $expenses / 100),
            Stat::make('Profit', $netProfit / 100),
        ];
    }
}

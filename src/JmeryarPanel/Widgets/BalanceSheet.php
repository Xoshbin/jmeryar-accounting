<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Widgets;

use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\Setting;

class BalanceSheet extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $defaultCurrecny = Setting::first()?->currency->symbol;

        $startDate = !is_null($this->filters['startDate'] ?? null)
            ? Carbon::parse($this->filters['startDate'])->startOfDay()
            : null;

        $endDate = !is_null($this->filters['endDate'] ?? null)
            ? Carbon::parse($this->filters['endDate'])->endOfDay()
            : now()->endOfDay();

        // Calculate Total Balances
        $totalAssets = $this->calculateBalance('Asset', $startDate, $endDate);
        $totalLiabilities = $this->calculateBalance('Liability', $startDate, $endDate);
        $totalEquity = $this->calculateBalance('Equity', $startDate, $endDate);
        $totalRevenues = $this->calculateBalance('Revenue', $startDate, $endDate);
        $totalExpenses = $this->calculateBalance('Expense', $startDate, $endDate);

        // Profit/Loss = Revenues - Expenses
        $profitOrLoss = $totalRevenues - $totalExpenses;

        // Adjust Equity to include Profit/Loss
        $totalEquity += $profitOrLoss;

        // Return Stats
        return [
            Stat::make('Assets', $defaultCurrecny . $totalAssets),
            Stat::make('Liabilities', $defaultCurrecny .  $totalLiabilities),
            Stat::make('Equity', $defaultCurrecny .  $totalEquity),
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

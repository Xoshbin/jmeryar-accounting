<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Widgets;

use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Xoshbin\JmeryarAccounting\Models\Account;

class BalanceSheet extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $startDate = ! is_null($this->filters['startDate'] ?? null)
            ? Carbon::parse($this->filters['startDate'])
            : null;

        $endDate = ! is_null($this->filters['endDate'] ?? null)
            ? Carbon::parse($this->filters['endDate'])
            : now();

        // Helper function to calculate balance from debit and credit
        $calculateBalance = function ($accountType) use ($startDate, $endDate) {
            return Account::where('type', $accountType)
                ->with(['journalEntries' => function ($query) use ($startDate, $endDate) {
                    if ($startDate && $endDate) {
                        $query->whereBetween('created_at', [$startDate, $endDate]);
                    }
                }])
                ->get()
                ->map(function ($account) {
                    $debits = $account->journalEntries->sum('debit');
                    $credits = $account->journalEntries->sum('credit');

                    return $account->type === 'Asset' || $account->type === 'Expense'
                        ? $debits - $credits // Debit-normal accounts
                        : $credits - $debits; // Credit-normal accounts
                })
                ->sum();
        };

        // Calculate Total Balances
        $totalAssets = $calculateBalance('Asset');
        $totalLiabilities = $calculateBalance('Liability');
        $totalEquity = $calculateBalance('Equity');
        $totalRevenues = $calculateBalance('Revenue');
        $totalExpenses = $calculateBalance('Expense');

        // Profit/Loss = Revenues - Expenses
        $profitOrLoss = $totalRevenues - $totalExpenses;

        // Adjust Equity to include Profit/Loss
        $totalEquity += $profitOrLoss;

        // Return Stats
        return [
            Stat::make('Assets', $totalAssets),
            Stat::make('Liabilities', $totalLiabilities),
            Stat::make('Equity', $totalEquity),
        ];
    }
}

<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Pages;

use Filament\Pages\Page;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting;
use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\Setting;

class ProfitLoss extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static string $view = 'jmeryar-accounting::pages.profit-loss';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $cluster = Accounting::class;

    public static function getNavigationLabel(): string
    {
        return __('jmeryar-accounting::profit_loss.title');
    }

    public function getTitle(): string
    {
        return __('jmeryar-accounting::profit_loss.title');
    }

    public function getIncomeStatementData(): array
    {
        $defaultCurrency = Setting::first()?->currency; // Default currency object

        $revenues = Account::where('type', 'Revenue')->with('journalEntries')->get();
        $expenses = Account::where('type', 'Expense')->with('journalEntries')->get();
        $defaultCurrecny = $defaultCurrency?->symbol; // Default currency symbol (e.g., IQD)

        // Helper function for currency conversion
        $convertToDefault = function ($entry) use ($defaultCurrency) {
            $associated = $entry->bills()->first() ?? $entry->invoices()->first();
            $entryCurrency = $associated?->currency;

            // Default to no conversion if the currency matches the default currency
            if (!$entryCurrency || $entryCurrency->id === $defaultCurrency->id) {
                $exchangeRate = 1;
            } else {
                // Fetch the exchange rate
                $exchangeRate = \Xoshbin\JmeryarAccounting\Models\ExchangeRate::where('base_currency_id', $entryCurrency->id)
                    ->where('target_currency_id', $defaultCurrency->id)
                    ->value('rate') ?: 1; // Fallback to 1 if no rate is found
            }

            // Convert amounts using the correct operation (divide for base->target conversion)
            $debit = $entry->debit / $exchangeRate;
            $credit = $entry->credit / $exchangeRate;

            return ['debit' => $debit, 'credit' => $credit];
        };

        // Calculate each account's total revenue and total expense
        $revenues = $revenues->map(function ($account) use ($convertToDefault) {
            $total = $account->journalEntries->sum(function ($entry) use ($convertToDefault) {
                $converted = $convertToDefault($entry);
                return $converted['credit'] - $converted['debit']; // Credit - Debit for Revenue
            });

            return [
                'name' => $account->name,
                'total' => $total,
            ];
        });

        $expenses = $expenses->map(function ($account) use ($convertToDefault) {
            $total = $account->journalEntries->sum(function ($entry) use ($convertToDefault) {
                $converted = $convertToDefault($entry);
                return $converted['debit'] - $converted['credit']; // Debit - Credit for Expense
            });

            return [
                'name' => $account->name,
                'total' => $total,
            ];
        });

        // Summing up the totals for all revenues and expenses
        $totalRevenue = $revenues->sum('total');
        $totalExpenses = $expenses->sum('total');

        // Calculate gross profit
        $grossProfit = $totalRevenue - $totalExpenses;

        return compact('revenues', 'expenses', 'totalRevenue', 'totalExpenses', 'grossProfit', 'defaultCurrecny');
    }
}

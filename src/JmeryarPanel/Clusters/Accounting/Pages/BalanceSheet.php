<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Pages;

use Filament\Pages\Page;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting;
use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\Setting;

class BalanceSheet extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'jmeryar-accounting::pages.balance-sheet';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $cluster = Accounting::class;

    public static function getNavigationLabel(): string
    {
        return __('jmeryar-accounting::balance_sheet.balance_sheet');
    }

    public function getTitle(): string
    {
        return __('jmeryar-accounting::balance_sheet.balance_sheet');
    }

    public function getBalanceData(): array
    {
        $defaultCurrency = Setting::first()?->currency; // Default currency object

        // Helper function for currency conversion
        $convertToDefault = function ($entry) use ($defaultCurrency) {
            $associated = $entry->bills()->first() ?? $entry->invoices()->first();
            $entryCurrency = $associated?->currency;

            // Default to no conversion if the currency matches the default currency
            if (! $entryCurrency || $entryCurrency->id === $defaultCurrency->id) {
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

        // Prepare data for assets, liabilities, and equity
        $data = [
            'assets' => Account::where('type', 'Asset')->with('journalEntries')->get()->map(function ($account) use ($convertToDefault) {
                $total = $account->journalEntries->sum(function ($entry) use ($convertToDefault) {
                    $converted = $convertToDefault($entry);

                    return $converted['debit'] - $converted['credit'];
                });

                return [
                    'name' => $account->name,
                    'total' => $total,
                ];
            }),
            'liabilities' => Account::where('type', 'Liability')->with('journalEntries')->get()->map(function ($account) use ($convertToDefault) {
                $total = $account->journalEntries->sum(function ($entry) use ($convertToDefault) {
                    $converted = $convertToDefault($entry);

                    return $converted['credit'] - $converted['debit'];
                });

                return [
                    'name' => $account->name,
                    'total' => $total,
                ];
            }),
            'equity' => Account::where('type', 'Equity')->with('journalEntries')->get()->map(function ($account) use ($convertToDefault) {
                $total = $account->journalEntries->sum(function ($entry) use ($convertToDefault) {
                    $converted = $convertToDefault($entry);

                    return $converted['debit'] - $converted['credit'];
                });

                return [
                    'name' => $account->name,
                    'total' => $total,
                ];
            }),
            'defaultCurrecny' => $defaultCurrency?->symbol,
        ];

        // Calculate totals
        $totals = [
            'assets' => $data['assets']->sum('total'),
            'liabilities' => $data['liabilities']->sum('total'),
        ];

        // Derive equity using the accounting equation
        $totals['equity'] = $totals['assets'] - $totals['liabilities'];

        return compact('data', 'totals');
    }
}

<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Pages;

use Filament\Pages\Page;
use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\Setting;

class BalanceSheet extends Page
{
    //    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'jmeryar-accounting::pages.balance-sheet';

    protected static ?string $navigationGroup = 'Reports';

    public static function getNavigationLabel(): string
    {
        return __('jmeryar-accounting::balance_sheet.balance_sheet');
    }

    public function getBalanceData(): array
    {
        $data = [
            'assets' => Account::where('type', 'Asset')->with('journalEntries')->get(),
            'liabilities' => Account::where('type', 'Liability')->with('journalEntries')->get(),
            'equity' => Account::where('type', 'Equity')->with('journalEntries')->get(),
            'defaultCurrecny' => Setting::first()?->currency->symbol,

        ];

        // Calculate totals
        $totals = [
            'assets' => $data['assets']->sum(fn($account) => $account->journalEntries->sum('debit') - $account->journalEntries->sum('credit')),
            'liabilities' => $data['liabilities']->sum(fn($account) => $account->journalEntries->sum('credit') - $account->journalEntries->sum('debit')),
        ];

        // Derive equity using the accounting equation
        $totals['equity'] = $totals['assets'] - $totals['liabilities'];

        return compact('data', 'totals');
    }
}

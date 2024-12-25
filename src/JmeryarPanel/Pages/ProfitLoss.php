<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Pages;

use Filament\Pages\Page;
use Xoshbin\JmeryarAccounting\Models\Account;

class ProfitLoss extends Page
{
    //    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'jmeryar-accounting::pages.profit-loss';

    protected static ?string $navigationGroup = 'Reports';

    public function getIncomeStatementData(): array
    {
        $revenues = Account::where('type', 'Revenue')->with('journalEntries')->get();
        $expenses = Account::where('type', 'Expense')->with('journalEntries')->get();

        // Calculate totals
        $totalRevenue = $revenues->sum(fn ($account) => $account->journalEntries->sum('credit') - $account->journalEntries->sum('debit'));
        $totalExpenses = $expenses->sum(fn ($account) => $account->journalEntries->sum('debit') - $account->journalEntries->sum('credit'));

        $grossProfit = $totalRevenue - $totalExpenses;

        return compact('revenues', 'expenses', 'totalRevenue', 'totalExpenses', 'grossProfit');
    }
}

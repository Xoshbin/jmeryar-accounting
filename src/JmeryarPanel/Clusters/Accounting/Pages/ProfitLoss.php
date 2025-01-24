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
        $revenues = Account::where('type', 'Revenue')->with('journalEntries')->get();
        $expenses = Account::where('type', 'Expense')->with('journalEntries')->get();
        $defaultCurrecny = Setting::first()?->currency->symbol;

        // Calculate totals
        $totalRevenue = $revenues->sum(fn ($account) => $account->journalEntries->sum('credit') - $account->journalEntries->sum('debit'));
        $totalExpenses = $expenses->sum(fn ($account) => $account->journalEntries->sum('debit') - $account->journalEntries->sum('credit'));

        $grossProfit = $totalRevenue - $totalExpenses;

        return compact('revenues', 'expenses', 'totalRevenue', 'totalExpenses', 'grossProfit', 'defaultCurrecny');
    }
}

<?php

namespace Xoshbin\JmeryarAccounting\Services;

use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\ExchangeRate;
use Xoshbin\JmeryarAccounting\Models\Setting;

class Calculator
{
    public static function calculateBalance($accountType, $startDate, $endDate): int
    {
        $defaultCurrency = Setting::first()?->currency; // Default currency (e.g., IQD)

        return Account::where('type', $accountType)
            ->whereHas('journalEntries', function ($query) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }
            })
            ->get()
            ->sum(function ($account) use ($startDate, $endDate, $defaultCurrency) {
                return $account->journalEntries
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum(function ($entry) use ($defaultCurrency) {
                        // Check if JournalEntry is linked to a Bill or Invoice
                        $associated = $entry->bills()->first() ?? $entry->invoices()->first();
                        $entryCurrency = $associated?->currency;

                        // Default to the current currency if no associated Bill/Invoice
                        if (! $entryCurrency || $entryCurrency->id === $defaultCurrency->id) {
                            $exchangeRate = 1; // Default 1:1 rate for the same currency
                        } else {
                            // Get exchange rate from the database
                            $exchangeRate = ExchangeRate::where('base_currency_id', $entryCurrency->id)
                                ->where('target_currency_id', $defaultCurrency->id)
                                ->value('rate') ?: 1; // Fallback to 1 if no rate is found
                        }

                        // Convert amounts to default currency using correct operation
                        if ($exchangeRate !== 1) {
                            // Default assumes rate is for base -> target (e.g., USD -> IQD)
                            $debit = $entry->debit / $exchangeRate; // Divide for conversion
                            $credit = $entry->credit / $exchangeRate; // Divide for conversion
                        } else {
                            $debit = $entry->debit;
                            $credit = $entry->credit;
                        }

                        // Calculate the balance based on account type
                        return in_array($entry->account->type, ['Asset', 'Expense'])
                            ? $debit - $credit
                            : $credit - $debit;
                    });
            });
    }
}

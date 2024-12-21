<?php

namespace Xoshbin\JmeryarAccounting\Services;

use Xoshbin\JmeryarAccounting\Models\Bill;
use Xoshbin\JmeryarAccounting\Models\JournalEntry;
use Xoshbin\JmeryarAccounting\Models\Account;

class JournalEntriesService
{
    public function createBillJournalEntries(Bill $bill): void
    {
        // Debit the Expense Account for the untaxed portion
        $expenseEntry = JournalEntry::create([
            'account_id' => $bill->expense_account_id, // Expense account for the untaxed portion
            'debit' => $bill->untaxed_amount,
            'credit' => 0,
            //            'label' => $bill->billItems->first()->product->name, //TODO:: add the name of the product as label
        ]);

        // Credit Accounts Payable for the total amount (untaxed + tax)
        $accountsPayableEntry = JournalEntry::create([
            'account_id' => $bill->liability_account_id, // Accounts Payable
            'debit' => 0, // Total amount
            'credit' => $bill->total_amount,
            //            'label' => '',
        ]);

        // Step 4: Attach the journal entries to the bill
        $bill->journalEntries()->attach([
            $accountsPayableEntry->id,
            $expenseEntry->id,
        ]);

        // Create tax entry only if tax amount is not null and greater than zero
        if (!is_null($bill->tax_amount) && $bill->tax_amount > 0.0) {
            $taxPaidAccount = Account::where('name', 'Tax Paid') // Assuming the "Tax Paid" account exists
                ->first();

            if ($taxPaidAccount) {
                $taxPaidEntry = JournalEntry::create([
                    'account_id' => $taxPaidAccount->id,
                    'debit' => $bill->tax_amount,
                    'credit' => 0,
                    // 'label' => $bill->taxes->first()->name, //TODO:: same as the product, add the name of the tax
                ]);

                // Attach tax journal entry to the invoice
                $bill->journalEntries()->attach($taxPaidEntry->id);
            }
        }
    }

    /**
     * Delete all journal entries related to the bill.
     */
    public function deleteBillJournalEntries(Bill $bill): void
    {
        // Assuming there is a relationship between Bill and JournalEntry
        foreach ($bill->journalEntries as $entry) {
            $entry->deleteQuietly();
        }
    }
}

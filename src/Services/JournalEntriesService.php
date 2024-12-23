<?php

namespace Xoshbin\JmeryarAccounting\Services;

use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\Bill;
use Xoshbin\JmeryarAccounting\Models\Invoice;
use Xoshbin\JmeryarAccounting\Models\JournalEntry;

class JournalEntriesService
{
    public function createBillJournalEntries(Bill $bill): void
    {
        // Only check total_amount as validation
        if ($bill->total_amount <= 0) {
            return;
        }

        // Delete any existing entries before creating new ones
        $this->deleteBillJournalEntries($bill);

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
        if (! is_null($bill->tax_amount) && $bill->tax_amount > 0.0) {
            $taxPaidAccount = Account::where('name', 'Tax Payable') // Assuming the "Tax Payable" account exists
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
        // Simplified deletion process
        $bill->journalEntries()->each(function ($entry) {
            $entry->deleteQuietly();
        });
        $bill->journalEntries()->detach();
    }

    /**
     * Create journal entries for the invoice.
     */
    public function createInvoiceJournalEntries(Invoice $invoice): void
    {
        // Revenue entry (credit)
        $revenueEntry = JournalEntry::create([
            'account_id' => $invoice->revenue_account_id,
            'debit' => 0,
            'credit' => $invoice->untaxed_amount,
        ]);

        // Create tax entry only if tax amount is not null and greater than zero
        if (! is_null($invoice->tax_amount) && $invoice->tax_amount > 0) {
            $taxReceivableAccount = Account::where('name', 'Tax Received')->first();

            if ($taxReceivableAccount) {
                $taxReceivableEntry = JournalEntry::create([
                    'account_id' => $taxReceivableAccount->id,
                    'credit' => $invoice->tax_amount,
                    'debit' => 0,
                ]);

                // Attach tax journal entry to the invoice
                $invoice->journalEntries()->attach($taxReceivableEntry->id);
            }
        }

        // Accounts receivable entry (debit)
        $accountsReceivableEntry = JournalEntry::create([
            'account_id' => $invoice->inventory_account_id,
            'debit' => $invoice->total_amount,
            'credit' => 0,
        ]);

        // Attach journal entries to the invoice
        $invoice->journalEntries()->attach([
            $revenueEntry->id,
            $accountsReceivableEntry->id,
        ]);
    }

    /**
     * Delete all journal entries related to the invoice.
     */
    public function deleteInvoiceJournalEntries(Invoice $invoice): void
    {
        foreach ($invoice->journalEntries as $entry) {
            $entry->delete();
        }
    }
}

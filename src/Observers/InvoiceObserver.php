<?php

namespace Xoshbin\JmeryarAccounting\Observers;

use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\Invoice;
use Xoshbin\JmeryarAccounting\Models\JournalEntry;
use Xoshbin\JmeryarAccounting\Models\Payment;
use Xoshbin\JmeryarAccounting\Models\Transaction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;

class InvoiceObserver
{
    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice): void
    {
        $this->createInvoiceJournalEntries($invoice);
    }

    /**
     * Handle the Invoice "updated" event.
     */
    public function updated(Invoice $invoice): void
    {
        // Delete existing journal entries for the invoice
        $this->deleteInvoiceJournalEntries($invoice);

        // Recreate journal entries with updated amounts
        $this->createInvoiceJournalEntries($invoice);
    }

    /**
     * Handle the Invoice "deleting" event.
     */
    public function deleting(Invoice $invoice): void
    {
        // To ensure the InvoiceItemObserver `deleted()` event is triggered,
        // access `invoiceItems` in the `deleting()` event instead of `deleted()`.
        foreach ($invoice->invoiceItems as $item) {
            $item->delete(); // This will trigger the InvoiceItemObserver `deleted()` event
        }

        // Delete all payments associated with this Invoice
        foreach ($invoice->payments as $payment) {
            // Delete all transactions and their journal entries associated with each payment
            foreach ($payment->transactions as $transaction) {
                $transaction->journalEntries()->delete(); // Delete related journal entries
                $transaction->delete(); // Delete the transaction
            }

            // Delete the payment itself
            $payment->delete();
        }

        // Delete invoice journal entries
        $this->deleteInvoiceJournalEntries($invoice);
    }

    /**
     * Create journal entries for the invoice.
     */
    protected function createInvoiceJournalEntries(Invoice $invoice): void
    {
        // Revenue entry (credit)
        $revenueEntry = JournalEntry::create([
            'account_id' => $invoice->revenue_account_id,
            'debit' => 0,
            'credit' => $invoice->untaxed_amount,
        ]);

        $taxReceivableAccountId = Account::where('name', 'Tax Received')
            ->first()
            ->id;

        $taxReceivableEntry = JournalEntry::create([
            'account_id' => $taxReceivableAccountId,
            'credit' => $invoice->tax_amount,
            'debit' => 0,
        ]);

        // Accounts receivable entry (debit)
        $accountsReceivableEntry = JournalEntry::create([
            'account_id' => $invoice->inventory_account_id,
            'debit' => $invoice->total_amount,
            'credit' => 0,
        ]);

        // Attach journal entries to the invoice
        $invoice->journalEntries()->attach([
            $revenueEntry->id,
            $taxReceivableEntry->id,
            $accountsReceivableEntry->id,
        ]);
    }

    /**
     * Delete all journal entries related to the invoice.
     */
    protected function deleteInvoiceJournalEntries(Invoice $invoice): void
    {
        foreach ($invoice->journalEntries as $entry) {
            $entry->delete();
        }
    }
}

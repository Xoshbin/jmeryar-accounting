<?php

namespace Xoshbin\JmeryarAccounting\Observers;

use Xoshbin\JmeryarAccounting\Models\Invoice;
use Xoshbin\JmeryarAccounting\Services\JournalEntriesService;

class InvoiceObserver
{
    protected $journalEntryService;

    public function __construct(JournalEntriesService $journalEntryService)
    {
        $this->journalEntryService = $journalEntryService;
    }

    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice): void
    {
        $this->journalEntryService->createInvoiceJournalEntries($invoice);
    }

    /**
     * Handle the Invoice "updated" event.
     */
    public function updated(Invoice $invoice): void
    {
        $originalValues = $invoice->getOriginal();
        $newValues = $invoice->getAttributes();

        // Check if amounts actually changed
        if (
            $originalValues['total_amount'] != $newValues['total_amount'] ||
            $originalValues['untaxed_amount'] != $newValues['untaxed_amount'] ||
            $originalValues['tax_amount'] != $newValues['tax_amount']
        ) {

            $this->journalEntryService->deleteJournalEntries($invoice);
            $this->journalEntryService->createInvoiceJournalEntries($invoice);
        }
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
        $this->journalEntryService->deleteJournalEntries($invoice);
    }
}

<?php

namespace Xoshbin\JmeryarAccounting\Observers;

use Xoshbin\JmeryarAccounting\Models\Bill;
use Xoshbin\JmeryarAccounting\Services\JournalEntriesService;

class BillObserver
{
    protected $journalEntryService;

    public function __construct(JournalEntriesService $journalEntryService)
    {
        $this->journalEntryService = $journalEntryService;
    }

    /**
     * Handle the Bill "created" event.
     */
    public function created(Bill $bill): void
    {
        $this->journalEntryService->createBillJournalEntries($bill);
    }

    /**
     * Handle the Bill "updated" event.
     */
    public function updated(Bill $bill): void
    {
        // Skip if not updating totals
        if (!$bill->updating_total) {
            return;
        }

        $originalValues = $bill->getOriginal();
        $newValues = $bill->getAttributes();
        
        // Check if amounts actually changed
        if ($originalValues['total_amount'] != $newValues['total_amount'] ||
            $originalValues['untaxed_amount'] != $newValues['untaxed_amount'] ||
            $originalValues['tax_amount'] != $newValues['tax_amount']) {
            
            $this->journalEntryService->deleteBillJournalEntries($bill);
            $this->journalEntryService->createBillJournalEntries($bill);
        }
    }

    /**
     * Handle the Bill "deleted" event.
     */
    public function deleting(Bill $bill): void
    {
        // to trigger the BillItemObserver deleted() event we need to access billItems()
        // in deleting() event instead of the deleted() event, because at the deleted event the billItems
        // are already deleted from the database because of the cascade and the BillItemObserver delete() event
        // not triggered to delete JournalEntries or other related models
        // That is the only way to trigger BillItemObserver delete event
        // But it may introduce bugs in the future in case the bill not deleted from the database because of an error.
        foreach ($bill->billItems as $item) {
            $item->delete(); // This will trigger the BillItemObserver deleted() event
        }

        // Delete all payments associated with this Bill
        foreach ($bill->payments as $payment) {
            // Delete all transactions and their journal entries associated with each payment
            foreach ($payment->transactions as $transaction) {
                $transaction->journalEntries()->delete();
                $transaction->delete();
            }

            // Delete the payment itself
            $payment->delete();
        }

        // Delete bill journal entries
        $this->journalEntryService->deleteBillJournalEntries($bill);
    }
}

<?php

namespace Xoshbin\JmeryarAccounting\Observers;

use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\Bill;
use Xoshbin\JmeryarAccounting\Models\JournalEntry;
use Xoshbin\JmeryarAccounting\Models\Tax;
use Filament\Facades\Filament;

class BillObserver
{

    /**
     * Handle the Bill "created" event.
     */
    public function created(Bill $bill): void
    {
        $this->createBillJournalEntries($bill);
    }

    /**
     * Handle the Bill "updated" event.
     */
    public function updated(Bill $bill): void
    {
        // Delete existing journal entries for the bill
        $this->deleteBillJournalEntries($bill);

        // Recreate journal entries with updated amounts
        $this->createBillJournalEntries($bill);
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
        $this->deleteBillJournalEntries($bill);
    }

    protected function createBillJournalEntries(Bill $bill): void
    {
        // Step 1: Debit the Expense Account for the untaxed portion
        $expenseEntry = JournalEntry::create([
            'account_id' => $bill->expense_account_id, // Expense account for the untaxed portion
            'debit' => $bill->untaxed_amount,
            'credit' => 0,
//            'label' => $bill->billItems->first()->product->name, //TODO:: add the name of the product as label
        ]);

        // Step 2: Debit the Tax Paid (Expense or Asset) for the tax amount
        $taxPaidAccount = Account::where('name', 'Tax Paid') // Assuming the "Tax Paid" account exists
            ->first();

        $taxPaidEntry = JournalEntry::create([
            'account_id' => $taxPaidAccount->id,
            'debit' => $bill->tax_amount,
            'credit' => 0,
//            'label' => $bill->taxes->first()->name, //TODO:: same as the product, add the name of the tax
        ]);

        // Step 3: Credit Accounts Payable for the total amount (untaxed + tax)
        $accountsPayableEntry = JournalEntry::create([
            'account_id' => $bill->liability_account_id, // Accounts Payable
            'debit' => 0, // Total amount
            'credit' => $bill->total_amount,
//            'label' => '',
        ]);

        // Step 4: Attach the journal entries to the bill
        $bill->journalEntries()->attach([
            $expenseEntry->id,
            $taxPaidEntry->id,
            $accountsPayableEntry->id,
        ]);
    }

    /**
     * Delete all journal entries related to the bill.
     */
    protected function deleteBillJournalEntries(Bill $bill): void
    {
        // Assuming there is a relationship between Bill and JournalEntry
        foreach ($bill->journalEntries as $entry) {
            $entry->delete();
        }
    }
}

<?php

namespace Xoshbin\JmeryarAccounting\Observers;


use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\JournalEntry;
use Xoshbin\JmeryarAccounting\Models\Payment;
use Xoshbin\JmeryarAccounting\Models\Transaction;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        $this->createTransactionAndEntries($payment);
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        // Remove existing transaction and journal entries
        $this->deleteExistingTransactionAndEntries($payment);

        // Re-create the transaction and journal entries with updated values
        $this->createTransactionAndEntries($payment);
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        // Delete associated transaction and journal entries
        $this->deleteExistingTransactionAndEntries($payment);
    }

    /**
     * Create the transaction and associated journal entries for the payment.
     */
    protected function createTransactionAndEntries(Payment $payment): void
    {
        // Access the parent model (e.g., Invoice or Bill)
        $parent = $payment->paymentable;

        $transaction = Transaction::create([
            'date' => $payment->payment_date,
            'note' => 'Transaction for payment ID ' . $payment->id,
            'amount' => $payment->amount,
            'transaction_type' => $parent instanceof \Xoshbin\JmeryarAccounting\Models\Invoice ? 'Credit' : 'Debit',
        ]);

        $payment->transactions()->attach($transaction->id);

        // Create journal entries based on the parent type
        if ($parent instanceof \Xoshbin\JmeryarAccounting\Models\Invoice) {
            // Debit Cash/Bank account, Credit Accounts Receivable
            $this->createJournalEntry($payment, 'Cash', $payment->amount, 0);
            $this->createJournalEntry($payment, 'Accounts Receivable', 0, $payment->amount);
        } elseif ($parent instanceof \Xoshbin\JmeryarAccounting\Models\Bill) {
            // Credit Cash/Bank account, Debit Accounts Payable
            $this->createJournalEntry($payment, 'Cash', 0, $payment->amount); // Credit the Cash account
            $this->createJournalEntry($payment, 'Accounts Payable', $payment->amount, 0); // Debit Accounts Payable
        }

        // Update the status of the parent based on the payment amount
        $this->updateParentStatus($parent, $payment->amount);
    }

    /**
     * create a journal entry for the transaction
     */
    protected function createJournalEntry(Payment $payment, string $accountName, float $debit, float $credit): void
    {
        $account = Account::where('name', $accountName)->first();
        if ($account) {
            $journal_entry = JournalEntry::create([
                'account_id' => $account->id,
                'debit' => $debit,
                'credit' => $credit,
            ]);

            $payment->journalEntries()->attach([
                $journal_entry->id,
            ]);
        }
    }

    /**
     * Delete existing transaction and associated journal entries for a payment.
     */
    protected function deleteExistingTransactionAndEntries(Payment $payment): void
    {
        $transactions = $payment->transactions;
        foreach ($transactions as $transaction) {
            $transaction->journalEntries()->delete();
            $transaction->delete();
        }
    }

    /**
     * Update the status of the parent model (e.g., Invoice or Bill) based on the payment.
     */
    protected function updateParentStatus($parent, float $paymentAmount): void
    {
        if ($paymentAmount < $parent->total_amount) {
            $parent->status = 'Partial';
        } else {
            $parent->status = 'Paid';
        }
        $parent->save();
    }
}

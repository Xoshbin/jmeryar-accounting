<?php

namespace Xoshbin\JmeryarAccounting\Services;

use Xoshbin\JmeryarAccounting\Models\BillItem;

class BillService
{

    /**
     * Update the total amount of the associated bill.
     */
    public function updateBillTotal(BillItem $billItem): void
    {
        $bill = $billItem->bill;

        // Calculate the total amount including taxes
        $totalAmount = $bill->billItems->sum('total_cost');
        $taxAmount = $bill->billItems->sum('tax_amount');
        $untaxedAmount = $bill->billItems->sum('untaxed_amount');

        // Update the attributes and save the model
        $bill->untaxed_amount = $untaxedAmount;
        $bill->tax_amount = $taxAmount;
        $bill->total_amount = $totalAmount;
        $bill->saveQuietly(); // This will trigger the observer's updated method
    }
}

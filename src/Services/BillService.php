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
        $bill = $billItem->bill->fresh();  // Get fresh instance

        // Skip if already updating
        if ($bill->updating_total) {
            return;
        }

        // Set the updating_total flag before saving
        $bill->updating_total = true;

        // Calculate new totals from fresh bill items
        $totals = $bill->billItems()->get()->reduce(function ($carry, $item) {
            $carry['total'] += $item->total_cost;
            $carry['tax'] += $item->tax_amount;
            $carry['untaxed'] += $item->untaxed_amount;
            return $carry;
        }, ['total' => 0, 'tax' => 0, 'untaxed' => 0]);

        // Only save if values actually changed
        if ($bill->total_amount != $totals['total'] || 
            $bill->tax_amount != $totals['tax'] || 
            $bill->untaxed_amount != $totals['untaxed']) {
            
            $bill->total_amount = $totals['total'];
            $bill->tax_amount = $totals['tax'];
            $bill->untaxed_amount = $totals['untaxed'];
            $bill->save(); // Use regular save to trigger observer
        }
        
        // Reset the flag
        $bill->updating_total = false;
    }
}

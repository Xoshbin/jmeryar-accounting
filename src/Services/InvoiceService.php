<?php

namespace Xoshbin\JmeryarAccounting\Services;

use Xoshbin\JmeryarAccounting\Models\InvoiceItem;

class InvoiceService
{
    /**
     * Update the total amount of the associated invoice.
     */
    public function updateInvoiceTotal(InvoiceItem $invoiceItem): void
    {
        $invoice = $invoiceItem->invoice->fresh();  // Get fresh instance

        // Calculate new totals from fresh invoice items
        $totals = $invoice->invoiceItems()->get()->reduce(function ($carry, $item) {
            $carry['total'] += $item->total_price;
            $carry['tax'] += $item->tax_amount;
            $carry['untaxed'] += $item->untaxed_amount;

            return $carry;
        }, ['total' => 0, 'tax' => 0, 'untaxed' => 0]);

        // Only save if values actually changed
        if (
            $invoice->total_amount != $totals['total'] ||
            $invoice->tax_amount != $totals['tax'] ||
            $invoice->untaxed_amount != $totals['untaxed']
        ) {

            $invoice->total_amount = $totals['total'];
            $invoice->tax_amount = $totals['tax'];
            $invoice->untaxed_amount = $totals['untaxed'];
            $invoice->save(); // Use regular save to trigger observer
        }
    }
}

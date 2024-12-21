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
        $invoice = $invoiceItem->invoice;
        $totalAmount = $invoice->invoiceItems->sum('total_price');
        $invoice->update(['total_amount' => $totalAmount]);
    }
}

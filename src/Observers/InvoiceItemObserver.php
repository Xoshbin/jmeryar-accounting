<?php

namespace Xoshbin\JmeryarAccounting\Observers;

use Xoshbin\JmeryarAccounting\Models\InvoiceItem;
use Xoshbin\JmeryarAccounting\Models\JournalEntry;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;

class InvoiceItemObserver
{
    /**
     * Handle the InvoiceItem "created" event.
     */
    public function created(InvoiceItem $invoiceItem): void
    {
        $this->deductInventoryFromBatches($invoiceItem);
        $this->updateInvoiceTotal($invoiceItem);
    }

    /**
     * Handle the InvoiceItem "updated" event.
     */
    public function updated(InvoiceItem $invoiceItem): void
    {
        // Adjust inventory based on quantity changes
        $this->updateInventoryBatch($invoiceItem);

        // Update the invoice's total amount
        $this->updateInvoiceTotal($invoiceItem);
    }

    /**
     * Handle the InvoiceItem "deleted" event.
     */
    public function deleted(InvoiceItem $invoiceItem): void
    {
        $this->restoreInventoryToBatches($invoiceItem);
        $this->updateInvoiceTotal($invoiceItem);
        $this->deleteTaxes($invoiceItem);
    }

    /**
     * Update inventory quantities based on changes to the invoice item.
     */
    protected function updateInventoryBatch(InvoiceItem $invoiceItem): void
    {
        $originalQuantity = $invoiceItem->getOriginal('quantity');
        $newQuantity = $invoiceItem->quantity;
        $quantityDifference = $newQuantity - $originalQuantity;

        if ($quantityDifference > 0) {
            $this->deductInventoryFromBatches($invoiceItem, $quantityDifference);
        } elseif ($quantityDifference < 0) {
            $this->restoreInventoryToBatches($invoiceItem, abs($quantityDifference));
        }
    }

    /**
     * Deduct inventory quantity from batches for an invoice item, using FIFO logic.
     */
    protected function deductInventoryFromBatches(InvoiceItem $invoiceItem): void
    {
        $remainingQuantity = $invoiceItem->quantity;

        foreach ($invoiceItem->product->inventoryBatches()->oldest()->get() as $batch) {
            if ($batch->quantity >= $remainingQuantity) {
                $batch->decrement('quantity', $remainingQuantity);
                break;
            } else {
                $remainingQuantity -= $batch->quantity;
                $batch->update(['quantity' => 0]);
            }
        }
    }

    /**
     * Restore inventory to batches when an invoice item is deleted or quantity is reduced.
     */
    protected function restoreInventoryToBatches(InvoiceItem $invoiceItem, int $quantityToRestore = null): void
    {
        $restoreQuantity = $quantityToRestore ?? $invoiceItem->quantity;

        foreach ($invoiceItem->product->inventoryBatches()->oldest()->get() as $batch) {
            $batch->increment('quantity', $restoreQuantity);
            $restoreQuantity -= $batch->quantity;
            if ($restoreQuantity <= 0) {
                break;
            }
        }
    }

    /**
     * Update the total amount of the associated invoice.
     */
    protected function updateInvoiceTotal(InvoiceItem $invoiceItem): void
    {
        $invoice = $invoiceItem->invoice;
        $totalAmount = $invoice->invoiceItems->sum('total_price');
        $invoice->update(['total_amount' => $totalAmount]);
    }

    // Delete associated taxes
    private function deleteTaxes($invoiceItem)
    {
        $invoiceItem->taxes()->detach();
    }
}

<?php

namespace Xoshbin\JmeryarAccounting\Services;

use Xoshbin\JmeryarAccounting\Models\BillItem;
use Xoshbin\JmeryarAccounting\Models\InvoiceItem;

class InventoryBatchService
{
    /**
     * Update or create the inventory batch associated with the bill item.
     */
    public function updateBillInventoryBatch(BillItem $billItem): void
    {
        $product = $billItem->product;

        // Find or create an inventory batch linked to this bill item
        $inventoryBatch = $product->inventoryBatches()
            ->where('bill_item_id', $billItem->id)
            ->first();

        if ($inventoryBatch) {
            // Update the existing batch with new values
            $inventoryBatch->update([
                'quantity' => $billItem->quantity,
                'cost_price' => $billItem->cost_price,
                'unit_price' => $billItem->unit_price,
            ]);
        } else {
            // Create a new batch if none exists
            $product->inventoryBatches()->create([
                'bill_item_id' => $billItem->id,
                'quantity' => $billItem->quantity,
                'cost_price' => $billItem->cost_price,
                'unit_price' => $billItem->unit_price,
            ]);
        }
    }

    /**
     * Update inventory quantities based on changes to the invoice item.
     */
    public function updateInvoiceInventoryBatch(InvoiceItem $invoiceItem): void
    {
        $product = $invoiceItem->product;

        // Find or create an inventory batch linked to this bill item
        $inventoryBatch = $product->inventoryBatches()
            ->where('bill_item_id', $invoiceItem->id)
            ->first();

        if ($inventoryBatch) {
            // Update the existing batch with new values
            $inventoryBatch->update([
                'quantity' => $invoiceItem->quantity,
                'cost_price' => $invoiceItem->cost_price,
                'unit_price' => $invoiceItem->unit_price,
            ]);
        } else {
            // Create a new batch if none exists
            $product->inventoryBatches()->create([
                'bill_item_id' => $invoiceItem->id,
                'quantity' => $invoiceItem->quantity,
                'cost_price' => $invoiceItem->cost_price,
                'unit_price' => $invoiceItem->unit_price,
            ]);
        }
    }

    /**
     * Deduct inventory quantity from batches for an invoice item, using FIFO logic.
     */
    public function deductInventoryFromBatches(InvoiceItem $invoiceItem): void
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
    public function restoreInventoryToBatches(InvoiceItem $invoiceItem, ?int $quantityToRestore = null): void
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
}

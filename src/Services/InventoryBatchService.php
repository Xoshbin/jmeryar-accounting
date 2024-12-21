<?php

namespace Xoshbin\JmeryarAccounting\Services;

use Xoshbin\JmeryarAccounting\Models\BillItem;

class InventoryBatchService
{
    /**
     * Update or create the inventory batch associated with the bill item.
     */
    public function updateInventoryBatch(BillItem $billItem): void
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
}

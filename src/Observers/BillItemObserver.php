<?php

namespace Xoshbin\JmeryarAccounting\Observers;

use Xoshbin\JmeryarAccounting\Models\BillItem;
use Xoshbin\JmeryarAccounting\Models\JournalEntry;
use Filament\Facades\Filament;

class BillItemObserver
{
    /**
     * Handle the BillItem "created" event.
     */
    public function created(BillItem $billItem): void
    {
        // Add the item into the inventory or update the existing batch
        $this->updateInventoryBatch($billItem);

        // Update the bill's total amount
        $this->updateBillTotal($billItem);
    }

    /**
     * Handle the BillItem "updated" event.
     */
    public function updated(BillItem $billItem): void
    {
        // Update the corresponding inventory batch
        $this->updateInventoryBatch($billItem);

        // Recalculate the total cost for the bill
        $this->updateBillTotal($billItem);
    }

    /**
     * Handle the BillItem "deleted" event.
     */
    public function deleted(BillItem $billItem): void
    {
        // Delete the corresponding inventory batch
        $billItem->product->inventoryBatches()
            ->where('bill_item_id', $billItem->id)
            ->delete();

        // Delete associated taxes
        $this->deleteTaxes($billItem);

        // Recalculate the total cost for the bill
        $this->updateBillTotal($billItem);
    }

    // Delete associated taxes
    private function deleteTaxes($billItem)
    {
        $billItem->taxes()->detach();
    }

    /**
     * Update or create the inventory batch associated with the bill item.
     */
    protected function updateInventoryBatch(BillItem $billItem): void
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
     * Update the total amount of the associated bill.
     */
    protected function updateBillTotal(BillItem $billItem): void
    {
        $bill = $billItem->bill;

        // Calculate the total amount including taxes
        $totalAmount = $bill->billItems->sum(function ($item) {
            return $item->total_cost + $item->tax_amount;
        });

        $bill->update(['total_amount' => $totalAmount]);
    }
}

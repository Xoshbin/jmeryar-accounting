<?php

namespace Xoshbin\JmeryarAccounting\Observers;

use Xoshbin\JmeryarAccounting\Models\BillItem;
use Xoshbin\JmeryarAccounting\Services\BillService;
use Xoshbin\JmeryarAccounting\Services\InventoryBatchService;

class BillItemObserver
{

    protected $billService;
    protected $inventoryBatchService;

    public function __construct(BillService $billService, InventoryBatchService $inventoryBatchService)
    {
        $this->billService = $billService;
        $this->inventoryBatchService = $inventoryBatchService;
    }

    /**
     * Handle the BillItem "created" event.
     */
    public function created(BillItem $billItem): void
    {
        $this->inventoryBatchService->updateInventoryBatch($billItem);

        // Add the item into the inventory or update the existing batch
        $this->billService->updateBillTotal($billItem);
    }

    /**
     * Handle the BillItem "updated" event.
     */
    public function updated(BillItem $billItem): void
    {
        $this->inventoryBatchService->updateInventoryBatch($billItem);

        // Add the item into the inventory or update the existing batch
        $this->billService->updateBillTotal($billItem);
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
        $this->billService->updateBillTotal($billItem);
    }

    // Delete associated taxes
    private function deleteTaxes($billItem)
    {
        $billItem->taxes()->detach();
    }
}

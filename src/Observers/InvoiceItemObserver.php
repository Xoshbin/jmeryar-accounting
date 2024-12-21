<?php

namespace Xoshbin\JmeryarAccounting\Observers;

use Xoshbin\JmeryarAccounting\Models\InvoiceItem;
use Xoshbin\JmeryarAccounting\Services\InventoryBatchService;
use Xoshbin\JmeryarAccounting\Services\InvoiceService;

class InvoiceItemObserver
{
    protected $inventoryBatchService;
    protected $invoiceService;

    public function __construct(InventoryBatchService $inventoryBatchService, InvoiceService $invoiceService)
    {
        $this->inventoryBatchService = $inventoryBatchService;
        $this->invoiceService = $invoiceService;
    }
    /**
     * Handle the InvoiceItem "created" event.
     */
    public function created(InvoiceItem $invoiceItem): void
    {
        $this->inventoryBatchService->deductInventoryFromBatches($invoiceItem);
        $this->invoiceService->updateInvoiceTotal($invoiceItem);
    }

    /**
     * Handle the InvoiceItem "updated" event.
     */
    public function updated(InvoiceItem $invoiceItem): void
    {
        // Adjust inventory based on quantity changes
        $this->inventoryBatchService->updateInvoiceInventoryBatch($invoiceItem);

        // Update the invoice's total amount
        $this->invoiceService->updateInvoiceTotal($invoiceItem);
    }

    /**
     * Handle the InvoiceItem "deleted" event.
     */
    public function deleted(InvoiceItem $invoiceItem): void
    {
        $this->inventoryBatchService->restoreInventoryToBatches($invoiceItem);
        $this->invoiceService->updateInvoiceTotal($invoiceItem);
        $this->deleteTaxes($invoiceItem);
    }



    // Delete associated taxes
    private function deleteTaxes($invoiceItem)
    {
        $invoiceItem->taxes()->detach();
    }
}

<?php

use Xoshbin\JmeryarAccounting\Database\Seeders\DatabaseSeeder;
use Xoshbin\JmeryarAccounting\Models\Bill;
use Xoshbin\JmeryarAccounting\Models\BillItem;
use Xoshbin\JmeryarAccounting\Models\Customer;
use Xoshbin\JmeryarAccounting\Models\Invoice;
use Xoshbin\JmeryarAccounting\Models\InvoiceItem;
use Xoshbin\JmeryarAccounting\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Xoshbin\JmeryarAccounting\Models\Supplier;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $supplier = Supplier::factory()->create();
    $bill = Bill::factory()->create(['supplier_id' => $supplier->id]);
    $this->product = Product::factory()->create();

    // Create bill items
    $this->billItem = BillItem::factory()->create([
        'bill_id' => $bill->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'unit_price' => 100,
        'tax_amount' => 0,
    ]);

    $this->product->inventoryBatches()->create([
        'expiry_date' => now()->addDays(30),
        'quantity' => 2,
        'cost_price' => 100,
        'unit_price' => 200
    ]);
});


it('attaches journal entries to the invoice', function () {
    // Create an invoice
    $customer = Customer::factory()->create();
    $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);

    // Create invoice items
    $invoiceItem = InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'unit_price' => 100,
        'tax_amount' => 0,
    ]);

    $invoice->invoiceItems()->save($invoiceItem);

    // Assert that journal entries are created and attached to the invoice
    expect($invoice->journalEntries()->count())->toBe(2);
});

it('restores inventory to the correct batches when an (invoice item) is deleted', function () {

    // Create an invoice with an item
    $customer = Customer::factory()->create();
    $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);

    $batch1 = $this->product->inventoryBatches()->oldest()->first();
    $originalBatch1Quantity = $batch1->quantity;

    // Create two invoice items with different batches
    $invoiceItem1 = InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $this->product->id,
        'quantity' => 1,
        'unit_price' => 100,
        'tax_amount' => 0,
    ]);

    // Delete the invoice items
    $invoiceItem1->delete();

    // Assert that the batch quantities are restored
    expect($batch1->quantity)->toBe($originalBatch1Quantity);
});

it('restores inventory to the correct batches when an (invoice) is deleted', function () {

    // Create an invoice with an item
    $customer = Customer::factory()->create();
    $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);

    $batch1 = $this->product->inventoryBatches()->oldest()->first();
    $originalBatch1Quantity = $batch1->quantity;

    // Create two invoice items with different batches
    $invoiceItem1 = InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $this->product->id,
        'quantity' => 1,
        'unit_price' => 100,
        'tax_amount' => 0,
    ]);

    // Delete the invoice items
    $invoice->delete();

    // Assert that the batch quantities are restored
    expect($batch1->quantity)->toBe($originalBatch1Quantity);
});
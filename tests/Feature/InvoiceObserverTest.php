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
use Xoshbin\JmeryarAccounting\Models\Tax;
use Xoshbin\JmeryarAccounting\Observers\InvoiceItemObserver;

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
        'cost_price' => 100,
        'unit_price' => 200,
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
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'unit_price' => 200,
        'total_price' => 400, // 2 * 200
        'tax_amount' => 0,
    ]);

    // Assert that journal entries are created and attached to the invoice
    expect($invoice->journalEntries()->count())->toBe(2);
});

it('restores inventory to the correct batches when an (invoice item) is deleted', function () {

    // Create an invoice with an item
    $customer = Customer::factory()->create();
    $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);

    $batch = $this->product->inventoryBatches()->oldest()->first();
    $originalBatch1Quantity = $batch->quantity;

    // Create two invoice items with different batches
    $invoiceItem = InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $this->product->id,
        'quantity' => 1,
        'unit_price' => 200,
        'total_price' => 200, // 1 * 200
        'tax_amount' => 0,
    ]);

    $batch->refresh(); // update batch after each event
    expect($batch->quantity)->not()->toBe($originalBatch1Quantity);

    // Delete the invoice items
    $invoiceItem->delete();

    $batch->refresh(); // update batch after each event
    // Assert that the batch quantities are restored
    expect($batch->quantity)->toBe($originalBatch1Quantity);
});

it('restores inventory to the correct batches when an (invoice) is deleted', function () {

    // Create an invoice with an item
    $customer = Customer::factory()->create();
    $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);

    $batch = $this->product->inventoryBatches()->oldest()->first();
    $originalBatch1Quantity = $batch->quantity;

    // Create two invoice items with different batches
    $invoiceItem = InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $this->product->id,
        'quantity' => 1,
        'unit_price' => 200,
        'total_price' => 200, // 1 * 200
        'tax_amount' => 0,
    ]);

    $batch->refresh(); // update batch after each event
    expect($batch->quantity)->not()->toBe($originalBatch1Quantity);


    // Delete the invoice
    $invoice->delete();

    $batch->refresh(); // update batch after each event
    $batch = $this->product->inventoryBatches()->oldest()->first();

    // Assert that the batch quantities are restored
    expect($batch->quantity)->toBe($originalBatch1Quantity);
});

it('attaches the correct journal entries to the invoice', function () {
    // Create an invoice with an item
    $customer = Customer::factory()->create();
    $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'unit_price' => 200,
        'total_price' => 400, // 2 * 200
    ]);

    // Assert that two journal entries are created and attached to the invoice
    expect($invoice->journalEntries()->count())->toBe(2);

    // Assert that the journal entries have the correct amounts and accounts
    $debitEntry = $invoice->journalEntries()->where('debit', '>', 0)->first();
    $creditEntry = $invoice->journalEntries()->where('type', '>', 0)->first();

    expect($debitEntry->debit)->toBe(400.0);
    expect($creditEntry->credit)->toBe(0.0);
});

it('attaches two journal entries to the invoice when there is no tax', function () {
    // Create an invoice with no tax
    $customer = Customer::factory()->create();
    $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'unit_price' => 200,
        'total_price' => 400, // 2 * 200
        'tax_amount' => 0, // No tax
    ]);

    // Assert that exactly two journal entries are created and attached to the invoice
    expect($invoice->journalEntries()->count())->toBe(2);
});

it('attaches three journal entries to the invoice when there is tax', function () {
    // Create an invoice with tax
    $customer = Customer::factory()->create();
    $invoice = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'tax_amount' => (2 * 100) * 0.10 // add tax amount
    ]);

    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'unit_price' => 200,
        'total_price' => 400, // 2 * 200
        'tax_amount' => 10, // With tax
    ]);

    // Assert that exactly three journal entries are created and attached to the invoice
    expect($invoice->journalEntries()->count())->toBe(3);
});

it('updates inventory and journal entries when an invoice item quantity is updated', function () {
    $customer = Customer::factory()->create();
    $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);
    $batch = $this->product->inventoryBatches()->oldest()->first();

    $originalBatchQuantity = $batch->quantity;

    $invoiceItem = InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $this->product->id,
        'quantity' => 1,
        'unit_price' => 200,
        'total_price' => 200, // 1 * 200
        'tax_amount' => 0,
    ]);

    $invoiceItem->quantity = 2; // Increase quantity
    $invoiceItem->total_price = 400; // Increase total_price 2 * 200
    $invoiceItem->save();

    $batch->refresh();

    // Assert that the batch quantity is updated correctly
    expect($batch->quantity)->toBe($originalBatchQuantity - $invoiceItem->quantity);

    // Assert that the journal entries are updated (You'll need to add assertions for specific journal entry values)
    expect($invoice->journalEntries()->count())->toBe(2);

    // Assert that the journal entries have the correct amounts and accounts
    $debitEntry = $invoice->journalEntries()->where('debit', '>', 0)->first();
    $creditEntry = $invoice->journalEntries()->where('type', '>', 0)->first();

    expect($debitEntry->debit)->toBe(200.0);
    expect($creditEntry->credit)->toBe(0.0);
});


it('deletes taxes when an invoice item is deleted', function () {
    $customer = Customer::factory()->create();
    $invoice = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'tax_amount' => (2 * 100) * 0.10 // add tax amount
    ]);

    $invoiceItem = InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'unit_price' => 200,
        'total_price' => 400, // 2 * 200
        'tax_amount' => 10, // With tax
    ]);

    $tax = Tax::where('name', '15% Sales')->first();

    $invoiceItem->taxes()->attach([
        'tax_id' => $tax->id,
        'tax_amount' => (2 * 100) * 0.10
    ]);

    $invoiceItem->delete();

    expect($invoiceItem->taxes()->count())->toBe(0);
});

it('deletes taxes when an invoice is deleted', function () {
    $customer = Customer::factory()->create();
    $invoice = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'tax_amount' => (2 * 100) * 0.10 // add tax amount
    ]);

    $invoiceItem = InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'unit_price' => 200,
        'total_price' => 400, // 2 * 200
        'tax_amount' => 10, // With tax
    ]);

    $tax = Tax::where('name', '15% Sales')->first();

    $invoiceItem->taxes()->attach([
        'tax_id' => $tax->id,
        'tax_amount' => (2 * 100) * 0.10
    ]);

    $invoice->delete();

    expect($invoiceItem->taxes()->count())->toBe(0);
});
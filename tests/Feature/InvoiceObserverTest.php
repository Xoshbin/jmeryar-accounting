<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Xoshbin\JmeryarAccounting\Database\Seeders\DatabaseSeeder;
use Xoshbin\JmeryarAccounting\Models\Bill;
use Xoshbin\JmeryarAccounting\Models\BillItem;
use Xoshbin\JmeryarAccounting\Models\Customer;
use Xoshbin\JmeryarAccounting\Models\Invoice;
use Xoshbin\JmeryarAccounting\Models\InvoiceItem;
use Xoshbin\JmeryarAccounting\Models\Product;
use Xoshbin\JmeryarAccounting\Models\Supplier;
use Xoshbin\JmeryarAccounting\Models\Tax;
use Xoshbin\JmeryarAccounting\Models\Payment;
use Xoshbin\JmeryarAccounting\Models\Account;

/**
 * The journal entries for an invoice are recorded in two stages:
 *
 * Stage 1: When the invoice is issued
 * | Date       | Account              | Debit  | Credit |
 * |------------|----------------------|--------|--------|
 * | YYYY-MM-DD | Accounts Receivable  | Amount |        |
 * | YYYY-MM-DD | Sales Revenue        |        | Amount |
 * | YYYY-MM-DD | Tax Payable Account  |        | Amount | 
 *
 * Stage 2: When the invoice is paid
 * | Date       | Account              | Debit  | Credit |
 * |------------|----------------------|--------|--------|
 * | YYYY-MM-DD | Cash/Bank Account    | Amount |        |
 * | YYYY-MM-DD | Accounts Receivable  |        | Amount | 
 *
 * Explanation:
 * - Accounts Receivable: Debited when the invoice is issued (asset), credited when paid.
 * - Sales Revenue: Credited to record the revenue earned from the sale.
 * - Tax Payable Account: Credited with the tax amount collected from the customer.
 * - Cash/Bank Account: Debited when the invoice is paid, increasing the balance.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $quantity = 2;
    $costPrice = 100;
    $taxPercent = 15;

    $this->seed(DatabaseSeeder::class);
    $supplier = Supplier::factory()->create();
    $this->customer = Customer::factory()->create();
    $this->product = Product::factory()->create();
    $this->bill = createBill($supplier, $quantity, $costPrice, $taxPercent);

    createBillItem($this->bill, $this->product, $quantity, $costPrice, $taxPercent);
});

function createBill($supplier, $quantity, $costPrice, $taxPercent = 0): Bill
{
    $bill = Bill::factory()->create([
        'supplier_id' => $supplier->id,
        'total_amount' => ($quantity * $costPrice) + (($quantity * $costPrice) * ($taxPercent / 100)),
        'tax_amount' => ($quantity * $costPrice) * ($taxPercent / 100),
        'untaxed_amount' => $quantity * $costPrice,
        'amount_due' => ($quantity * $costPrice) + (($quantity * $costPrice) * ($taxPercent / 100)),
    ]);

    return $bill;
}

function createBillItem($bill, $product, $quantity, $costPrice, $taxPercent = 0): BillItem
{
    $billItem = BillItem::factory()->create([
        'bill_id' => $bill->id,
        'product_id' => $product->id,
        'quantity' => $quantity,
        'cost_price' => $costPrice,
        'total_cost' => ($quantity * $costPrice) + (($quantity * $costPrice) * ($taxPercent / 100)),
        'tax_amount' => ($quantity * $costPrice) * ($taxPercent / 100),
        'untaxed_amount' => $quantity * $costPrice,
    ]);

    return $billItem;
}

function createInvoiceItem($invoice, $product, $quantity, $unitPrice, $taxPercent = 0): InvoiceItem
{
    $invoiceItem = InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'product_id' => $product->id,
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
        'total_price' => ($quantity * $unitPrice) + (($quantity * $unitPrice) * ($taxPercent / 100)),
        'tax_amount' => ($quantity * $unitPrice) * ($taxPercent / 100),
        'untaxed_amount' => $quantity * $unitPrice,
    ]);

    return $invoiceItem;
}

function createPayment($bill, $amount, $paymentMethod, $paymentType, $currencyId, $exchangeRate, $amountInInvoiceCurrency): Payment
{
    $payment = $bill->payments()->create([
        'amount' => $amount,
        'payment_date' => now(),
        'payment_method' => $paymentMethod,
        'payment_type' => $paymentType,
        'currency_id' => $currencyId,
        'exchange_rate' => $exchangeRate,
        'amount_in_invoice_currency' => $amountInInvoiceCurrency,
    ]);

    return $payment;
}

function createInvoice($customer, $quantity, $unit_price, $taxPercent = 0): Invoice
{
    $invoice = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'total_amount' => ($quantity * $unit_price) + (($quantity * $unit_price) * ($taxPercent / 100)),
        'tax_amount' => ($quantity * $unit_price) * ($taxPercent / 100),
        'untaxed_amount' => $quantity * $unit_price,
        'amount_due' => ($quantity * $unit_price) + (($quantity * $unit_price) * ($taxPercent / 100)),
    ]);

    return $invoice;
}

it('attaches journal entries to the invoice without tax', function () {
    $quantity = 2;
    $unitPrice = 200;
    $taxPercent = 0;

    // Create an invoice

    $invoice = createInvoice($this->customer, $quantity, $unitPrice, $taxPercent);

    $invoiceItem = createInvoiceItem($invoice, $this->product, $quantity, $unitPrice, $taxPercent);

    // Assert that journal entries are created and attached to the invoice
    expect($invoice->journalEntries()->count())->toBe(2);
});

it('restores inventory to the correct batches when an (invoice item) is deleted', function () {
    $quantity = 1;
    $unitPrice = 200;
    $taxPercent = 0;

    // Create an invoice

    $invoice = createInvoice($this->customer, $quantity, $unitPrice, $taxPercent);

    $batch = $this->product->inventoryBatches()->oldest()->first();
    $originalBatch1Quantity = $batch->quantity;

    $invoiceItem = createInvoiceItem($invoice, $this->product, $quantity, $unitPrice, $taxPercent);

    $batch->refresh(); // update batch after each event
    expect($batch->quantity)->not()->toBe($originalBatch1Quantity);

    // Delete the invoice items
    $invoiceItem->delete();

    $batch->refresh(); // update batch after each event
    // Assert that the batch quantities are restored
    expect($batch->quantity)->toBe($originalBatch1Quantity);
});

it('restores inventory to the correct batches when an (invoice) is deleted', function () {

    $quantity = 1;
    $unitPrice = 200;
    $taxPercent = 0;

    // Create an invoice

    $invoice = createInvoice($this->customer, $quantity, $unitPrice, $taxPercent);

    $batch = $this->product->inventoryBatches()->oldest()->first();
    $originalBatch1Quantity = $batch->quantity;

    $invoiceItem = createInvoiceItem($invoice, $this->product, $quantity, $unitPrice, $taxPercent);

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
    /**
     * The journal entries for an invoice are recorded in two stages:
     *
     * Stage 1: When the invoice is issued
     * | Date       | Account              | Debit  | Credit |
     * |------------|----------------------|--------|--------|
     * | YYYY-MM-DD | Accounts Receivable  | 230    |        |
     * | YYYY-MM-DD | Sales Revenue        |        | 200    |
     * | YYYY-MM-DD | Tax Payable Account  |        | 30     | 

     *
     * Stage 2: When the invoice is paid
     * | Date       | Account              | Debit  | Credit |
     * |------------|----------------------|--------|--------|
     * | YYYY-MM-DD | Cash/Bank Account    | 230    |        |
     * | YYYY-MM-DD | Accounts Receivable  |        | 230    | 
     *
     * Explanation:
     * - Accounts Receivable: Debited when the invoice is issued (asset), credited when paid.
     * - Sales Revenue: Credited to record the revenue earned from the sale.
     * - Tax Payable Account: Credited with the tax amount collected from the customer.
     * - Cash/Bank Account: Debited when the invoice is paid, increasing the balance.
     */

    // Create an invoice with an item
    $quantity = 1;
    $unitPrice = 200;
    $taxPercent = 15;

    // Create an invoice

    $invoice = createInvoice($this->customer, $quantity, $unitPrice, $taxPercent);

    $invoiceItem = createInvoiceItem($invoice, $this->product, $quantity, $unitPrice, $taxPercent);

    $tax = Tax::where('name', '15% Sales')->first();

    $invoiceItem->taxes()->attach([
        'tax_id' => $tax->id,
        'tax_amount' => ($quantity * $unitPrice) * $taxPercent,
    ]);

    // Assert that three journal entries are created and attached to the bill
    expect($invoice->journalEntries()->count())->toBe(3);

    expect($invoice->total_amount)->toBe(230.0);
    expect($invoice->untaxed_amount)->toBe(200.0);
    expect($invoice->tax_amount)->toBe(30.0);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsReceivableEntry = $invoice->journalEntries()->where('debit', 230 * 100)->first(); // * 100 CastMoney
    $salesRevenueEntry = $invoice->journalEntries()->where('credit', 200 * 100)->first();
    $taxPayableEntry = $invoice->journalEntries()->where('credit', 30 * 100)->first();

    // Assert Accounts Receivable entry
    expect($accountsReceivableEntry->account_id)->toBe(Account::where('name', 'Accounts Receivable')->first()->id);
    expect($accountsReceivableEntry->debit)->toBe(230.0);

    // Assert Expense entry
    expect($salesRevenueEntry->account_id)->toBe(Account::where('type', Account::TYPE_REVENUE)->first()->id);
    expect($salesRevenueEntry->credit)->toBe(200.0);

    // Assert Tax Payable entry
    expect($taxPayableEntry->account_id)->toBe(Account::where('name', 'Tax Payable')->first()->id);
    expect($taxPayableEntry->credit)->toBe(30.0);

    // Ensure total debits equal total credits
    $totalDebits = $invoice->journalEntries()->sum('debit');
    $totalCredits = $invoice->journalEntries()->sum('credit');
    expect($totalDebits)->toBe($totalCredits);

    // Assert that the bill's untaxed_amount is correct
    expect($invoice->untaxed_amount)->toBe(200.0);
    expect($invoice->total_amount)->toBe(230.0);
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
        'tax_amount' => (2 * 100) * 0.10, // add tax amount
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
        'tax_amount' => (2 * 100) * 0.10, // add tax amount
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
        'tax_amount' => (2 * 100) * 0.10,
    ]);

    $invoiceItem->delete();

    expect($invoiceItem->taxes()->count())->toBe(0);
});

it('deletes taxes when an invoice is deleted', function () {
    $customer = Customer::factory()->create();
    $invoice = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'tax_amount' => (2 * 100) * 0.10, // add tax amount
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
        'tax_amount' => (2 * 100) * 0.10,
    ]);

    $invoice->delete();

    expect($invoiceItem->taxes()->count())->toBe(0);
});

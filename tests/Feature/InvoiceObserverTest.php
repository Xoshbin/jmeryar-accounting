<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Services\TestServices;
use Xoshbin\JmeryarAccounting\Database\Seeders\DatabaseSeeder;
use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\Currency;
use Xoshbin\JmeryarAccounting\Models\Customer;
use Xoshbin\JmeryarAccounting\Models\ExchangeRate;
use Xoshbin\JmeryarAccounting\Models\JournalEntry;
use Xoshbin\JmeryarAccounting\Models\Product;
use Xoshbin\JmeryarAccounting\Models\Supplier;
use Xoshbin\JmeryarAccounting\Models\Tax;

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
    $this->bill = TestServices::createBill($supplier, $quantity, $costPrice, $taxPercent);

    TestServices::createBillItem($this->bill, $this->product, $quantity, $costPrice, $taxPercent);
});

it('creates an invoice with correct attributes', function () {
    $quantity = 2;
    $unitPrice = 200;
    $taxPercent = 15;

    $invoice = TestServices::createInvoice($this->customer, $quantity, $unitPrice, $taxPercent);

    // Assert that the invoice has the correct attributes
    expect($invoice->customer_id)->toBe($this->customer->id);
    expect($invoice->total_amount)->toBe(460.0);
    expect($invoice->tax_amount)->toBe(60.0);
    expect($invoice->untaxed_amount)->toBe(400.0);
});

it('creates an invoice without tax correctly', function () {
    $quantity = 2;
    $unitPrice = 200;
    $taxPercent = 0;

    $invoice = TestServices::createInvoice($this->customer, $quantity, $unitPrice, $taxPercent);

    // Assert that the invoice has the correct attributes
    expect($invoice->customer_id)->toBe($this->customer->id);
    expect($invoice->total_amount)->toBe(400.0);
    expect($invoice->tax_amount)->toBe(0.0);
    expect($invoice->untaxed_amount)->toBe(400.0);
});

it('attaches journal entries to the invoice without tax', function () {
    $quantity = 2;
    $unitPrice = 200;
    $taxPercent = 0;

    // Create an invoice

    $invoice = TestServices::createInvoice($this->customer, $quantity, $unitPrice, $taxPercent);

    $invoiceItem = TestServices::createInvoiceItem($invoice, $this->product, $quantity, $unitPrice, $taxPercent);

    // Assert that journal entries are created and attached to the invoice
    expect($invoice->journalEntries()->count())->toBe(2);
});

it('restores inventory to the correct batches when an (invoice item) is deleted', function () {
    $quantity = 1;
    $unitPrice = 200;
    $taxPercent = 0;

    // Create an invoice

    $invoice = TestServices::createInvoice($this->customer, $quantity, $unitPrice, $taxPercent);

    $batch = $this->product->inventoryBatches()->oldest()->first();
    $originalBatchQuantity = $batch->quantity;

    $invoiceItem = TestServices::createInvoiceItem($invoice, $this->product, $quantity, $unitPrice, $taxPercent);

    $batch->refresh(); // update batch after each event
    expect($batch->quantity)->not()->toBe($originalBatchQuantity);

    // Delete the invoice items
    $invoiceItem->delete();

    $batch->refresh(); // update batch after each event
    // Assert that the batch quantities are restored
    expect($batch->quantity)->toBe($originalBatchQuantity);
});

it('restores inventory to the correct batches when an (invoice) is deleted', function () {

    $quantity = 1;
    $unitPrice = 200;
    $taxPercent = 0;

    // Create an invoice

    $invoice = TestServices::createInvoice($this->customer, $quantity, $unitPrice, $taxPercent);

    $batch = $this->product->inventoryBatches()->oldest()->first();
    $originalBatch1Quantity = $batch->quantity;

    $invoiceItem = TestServices::createInvoiceItem($invoice, $this->product, $quantity, $unitPrice, $taxPercent);

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

    $invoice = TestServices::createInvoice($this->customer, $quantity, $unitPrice, $taxPercent);

    $invoiceItem = TestServices::createInvoiceItem($invoice, $this->product, $quantity, $unitPrice, $taxPercent);

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
    $quantity = 1;
    $unitPrice = 200;
    $taxPercent = 0;

    // Create an invoice

    $invoice = TestServices::createInvoice($this->customer, $quantity, $unitPrice, $taxPercent);

    $invoiceItem = TestServices::createInvoiceItem($invoice, $this->product, $quantity, $unitPrice, $taxPercent);

    // Assert that exactly two journal entries are created and attached to the invoice
    expect($invoice->journalEntries()->count())->toBe(2);
});

it('attaches three journal entries to the invoice when there is tax', function () {
    // Create an invoice with tax
    $quantity = 1;
    $unitPrice = 200;
    $taxPercent = 15;

    // Create an invoice

    $invoice = TestServices::createInvoice($this->customer, $quantity, $unitPrice, $taxPercent);

    $invoiceItem = TestServices::createInvoiceItem($invoice, $this->product, $quantity, $unitPrice, $taxPercent);

    $tax = Tax::where('name', '15% Sales')->first();

    $invoiceItem->taxes()->attach([
        'tax_id' => $tax->id,
        'tax_amount' => ($quantity * $unitPrice) * $taxPercent,
    ]);

    // Assert that exactly three journal entries are created and attached to the invoice
    expect($invoice->journalEntries()->count())->toBe(3);
});

it('deletes taxes when an invoice item is deleted', function () {
    $quantity = 1;
    $unitPrice = 200;
    $taxPercent = 15;

    // Create an invoice

    $invoice = TestServices::createInvoice($this->customer, $quantity, $unitPrice, $taxPercent);

    $invoiceItem = TestServices::createInvoiceItem($invoice, $this->product, $quantity, $unitPrice, $taxPercent);

    $tax = Tax::where('name', '15% Sales')->first();

    $invoiceItem->taxes()->attach([
        'tax_id' => $tax->id,
        'tax_amount' => ($quantity * $unitPrice) * $taxPercent,
    ]);

    $invoiceItem->delete();

    expect($invoiceItem->taxes()->count())->toBe(0);
});

it('deletes taxes when an invoice is deleted', function () {
    $quantity = 1;
    $unitPrice = 200;
    $taxPercent = 0;

    // Create an invoice

    $invoice = TestServices::createInvoice($this->customer, $quantity, $unitPrice, $taxPercent);

    $batch = $this->product->inventoryBatches()->oldest()->first();

    $invoiceItem = TestServices::createInvoiceItem($invoice, $this->product, $quantity, $unitPrice, $taxPercent);

    $invoice->delete();

    expect($invoiceItem->taxes()->count())->toBe(0);
});

it('creates an invoice with multiple items correctly', function () {
    $quantity1 = 2;
    $unitPrice1 = 200;
    $taxPercent1 = 15;

    $quantity2 = 3;
    $unitPrice2 = 100;
    $taxPercent2 = 5;

    $invoice = TestServices::createInvoice($this->customer, $quantity1, $unitPrice1, $taxPercent1);

    $invoiceItem1 = TestServices::createInvoiceItem($invoice, $this->product, $quantity1, $unitPrice1, $taxPercent1);

    $tax = Tax::where('name', '15% Sales')->first();

    $invoiceItem1->taxes()->attach([
        'tax_id' => $tax->id,
        'tax_amount' => ($quantity1 * $unitPrice1) * $taxPercent1,
    ]);

    $invoiceItem2 = TestServices::createInvoiceItem($invoice, $this->product, $quantity2, $unitPrice2, $taxPercent2);

    $tax = Tax::where('name', '5% Sales')->first();

    $invoiceItem2->taxes()->attach([
        'tax_id' => $tax->id,
        'tax_amount' => ($quantity2 * $unitPrice2) * $taxPercent2,
    ]);

    // Refresh the invoice to ensure the total_amount is updated
    $invoice->refresh();

    // Assert that the invoice's total_amount is correct
    expect($invoice->total_amount)->toBe(775.0);
    expect($invoice->untaxed_amount)->toBe(700.0);
    expect($invoice->tax_amount)->toBe(75.0);
});

it('attaches journal entries to the invoice', function () {
    $quantity = 2;
    $unitPrice = 200;
    $taxPercent = 0;

    $invoice = TestServices::createInvoice($this->customer, $quantity, $unitPrice, $taxPercent);

    $invoiceItem = TestServices::createInvoiceItem($invoice, $this->product, $quantity, $unitPrice, $taxPercent);

    // Assert that journal entries are created and attached to the invoice
    expect($invoice->journalEntries()->count())->toBe(2);

    // Assert that the invoice's untaxed_amount is correct
    expect($invoice->untaxed_amount)->toBe(400.0);
    expect($invoice->total_amount)->toBe(400.0);
});

it('attaches the correct journal entries when an invoice is paid without tax', function () {

    /**
     * The journal entries for an invoice are recorded in two stages:
     *
     * Stage 1: When the invoice is issued
     * | Date       | Account              | Debit  | Credit |
     * |------------|----------------------|--------|--------|
     * | YYYY-MM-DD | Accounts Receivable  | 400    |        |
     * | YYYY-MM-DD | Sales Revenue        |        | 400    |
     *
     * Stage 2: When the invoice is paid
     * | Date       | Account              | Debit  | Credit |
     * |------------|----------------------|--------|--------|
     * | YYYY-MM-DD | Cash/Bank Account    | 400    |        |
     * | YYYY-MM-DD | Accounts Receivable  |        | 400    |
     *
     * Explanation:
     * - Accounts Receivable: Debited when the invoice is issued (asset), credited when paid.
     * - Sales Revenue: Credited to record the revenue earned from the sale.
     * - Tax Payable Account: Credited with the tax amount collected from the customer.
     * - Cash/Bank Account: Debited when the invoice is paid, increasing the balance.
     */
    $quantity = 2;
    $unitPrice = 200;
    $taxPercent = 0;

    $invoice = TestServices::createInvoice($this->customer, $quantity, $unitPrice, $taxPercent);

    $invoiceItem = TestServices::createInvoiceItem($invoice, $this->product, $quantity, $unitPrice, $taxPercent);

    // Assert that exactly two journal entries are created and attached to the invoice
    expect($invoice->journalEntries()->count())->toBe(2);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsReceivableEntry = $invoice->journalEntries()->where('debit', 400 * 100)->first(); // * 100 CastMoney
    $salesRevenueEntry = $invoice->journalEntries()->where('credit', 400 * 100)->first();

    // Assert Accounts Receivable entry
    expect($accountsReceivableEntry->account_id)->toBe(Account::where('name', 'Accounts Receivable')->first()->id);
    expect($accountsReceivableEntry->debit)->toBe(400.0);

    // Assert Sales Revenue entry
    expect($salesRevenueEntry->account_id)->toBe(Account::where('type', Account::TYPE_REVENUE)->first()->id);
    expect($salesRevenueEntry->credit)->toBe(400.0);

    $currency_id = Currency::where('code', 'USD')->first()->id;

    // Stage 2: Pay the invoice
    $payment = TestServices::createPayment($invoice, 400, 'Cash', 'Income', $currency_id, 1, 400);

    // Assert that the transaction has two journal entries
    expect($payment->journalEntries()->count())->toBe(2);

    $journalEntriesCount = JournalEntry::count();
    // at this level there should be 7 records of journal entries
    // 3 journal entries for the bill and it's tax
    // 2 entries for the invoice
    // 2 entries for the payment
    expect($journalEntriesCount)->toBe(7);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsReceivableEntry = $payment->journalEntries()
        ->where('account_id', Account::where('name', 'Accounts Receivable')->first()->id)
        ->where('credit', 400 * 100)
        ->first();

    $cashEntry = $payment->journalEntries()
        ->where('account_id', Account::where('name', 'Cash')->first()->id)
        ->where('debit', 400 * 100)
        ->first();

    expect($accountsReceivableEntry)->not->toBeNull();
    expect($cashEntry)->not->toBeNull();

    $invoice->refresh();

    expect($invoice->status)->toBe('Paid');

    // Ensure total debits equal total credits
    $totalDebits = $invoice->journalEntries()->sum('debit');
    $totalCredits = $invoice->journalEntries()->sum('credit');

    expect($totalDebits)->toBe($totalCredits);

    // Assert that the invoice's untaxed_amount is correct
    expect($invoice->untaxed_amount)->toBe(400.0);
});

it('updates inventory and journal entries when an invoice item quantity is updated', function () {
    $quantity = 2;
    $unitPrice = 200;
    $taxPercent = 0;

    $invoice = TestServices::createInvoice($this->customer, $quantity, $unitPrice, $taxPercent);

    $invoiceItem = TestServices::createInvoiceItem($invoice, $this->product, $quantity, $unitPrice, $taxPercent);

    expect($invoice->journalEntries()->count())->toBe(2);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsReceivableEntry = $invoice->journalEntries()->where('debit', 400 * 100)->first(); // * 100 CastMoney
    $salesRevenueEntry = $invoice->journalEntries()->where('credit', 400 * 100)->first();

    // Assert Accounts Receivable entry
    expect($accountsReceivableEntry->account_id)->toBe(Account::where('name', 'Accounts Receivable')->first()->id);
    expect($accountsReceivableEntry->debit)->toBe(400.0);

    // Assert Sales Revenue entry
    expect($salesRevenueEntry->account_id)->toBe(Account::where('type', Account::TYPE_REVENUE)->first()->id);
    expect($salesRevenueEntry->credit)->toBe(400.0);

    // Ensure total debits equal total credits
    $totalDebits = $invoice->journalEntries()->sum('debit');
    $totalCredits = $invoice->journalEntries()->sum('credit');
    expect($totalDebits)->toBe($totalCredits);

    // Update the invoice item quantity
    $invoiceItem->quantity = 4;
    $invoiceItem->total_price = 800;
    $invoiceItem->untaxed_amount = 800;
    $invoiceItem->save();

    expect($invoiceItem->total_price)->toBe(800.0);
    expect($invoiceItem->untaxed_amount)->toBe(800.0);

    $invoice->refresh();

    // Assert that the journal entries are updated (You'll need to add assertions for specific journal entry values)
    expect($invoice->journalEntries()->count())->toBe(2);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsReceivableEntry = $invoice->journalEntries()->where('debit', 800 * 100)->first(); // * 100 CastMoney
    $salesRevenueEntry = $invoice->journalEntries()->where('credit', 800 * 100)->first();

    // Assert Accounts Receivable entry
    expect($accountsReceivableEntry->account_id)->toBe(Account::where('name', 'Accounts Receivable')->first()->id);
    expect($accountsReceivableEntry->debit)->toBe(800.0);

    // Assert Sales Revenue entry
    expect($salesRevenueEntry->account_id)->toBe(Account::where('type', Account::TYPE_REVENUE)->first()->id);
    expect($salesRevenueEntry->credit)->toBe(800.0);

    // Ensure total debits equal total credits
    $totalDebits = $invoice->journalEntries()->sum('debit');
    $totalCredits = $invoice->journalEntries()->sum('credit');
    expect($totalDebits)->toBe($totalCredits);

    // Assert that the invoice's untaxed_amount is correct
    expect($invoice->untaxed_amount)->toBe(800.0);
});

it('attaches the correct journal entries when an invoice is paid with tax', function () {
    $quantity = 2;
    $unitPrice = 200;
    $taxPercent = 15;

    $invoice = TestServices::createInvoice($this->customer, $quantity, $unitPrice, $taxPercent);

    $invoiceItem = TestServices::createInvoiceItem($invoice, $this->product, $quantity, $unitPrice, $taxPercent);

    $tax = Tax::where('name', '15% Sales')->first();

    $invoiceItem->taxes()->attach([
        'tax_id' => $tax->id,
        'tax_amount' => ($quantity * $unitPrice) * $taxPercent,
    ]);

    // Assert that exactly three journal entries are created and attached to the invoice
    expect($invoice->journalEntries()->count())->toBe(3);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsReceivableEntry = $invoice->journalEntries()->where('debit', 460 * 100)->first(); // * 100 CastMoney
    $salesRevenueEntry = $invoice->journalEntries()->where('credit', 400 * 100)->first();
    $taxPayableEntry = $invoice->journalEntries()->where('credit', 60 * 100)->first();

    // Assert Accounts Receivable entry
    expect($accountsReceivableEntry->account_id)->toBe(Account::where('name', 'Accounts Receivable')->first()->id);
    expect($accountsReceivableEntry->debit)->toBe(460.0);

    // Assert Sales Revenue entry
    expect($salesRevenueEntry->account_id)->toBe(Account::where('type', Account::TYPE_REVENUE)->first()->id);
    expect($salesRevenueEntry->credit)->toBe(400.0);

    // Assert Tax Payable entry
    expect($taxPayableEntry->account_id)->toBe(Account::where('name', 'Tax Payable')->first()->id);
    expect($taxPayableEntry->credit)->toBe(60.0);

    // stage 2: Pay the invoice
    $currency_id = Currency::where('code', 'USD')->first()->id;

    // Stage 2: Pay the invoice
    $payment = TestServices::createPayment($invoice, 460, 'Cash', 'Income', $currency_id, 1, 460);
    expect($payment->amount)->toBe(460.0);

    // Assert that the transaction has two journal entries
    expect($payment->journalEntries()->count())->toBe(2);

    $journalEntriesCount = JournalEntry::count();
    // at this level there should be 7 records of journal entries
    // 3 journal entries for the bill and it's tax
    // 3 entries for the invoice and it's tax
    // 2 entries for the payment
    expect($journalEntriesCount)->toBe(8);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsReceivableEntry = $payment->journalEntries()
        ->where('account_id', Account::where('name', 'Accounts Receivable')->first()->id)
        ->where('credit', 460 * 100)
        ->first();

    $cashEntry = $payment->journalEntries()
        ->where('account_id', Account::where('name', 'Cash')->first()->id)
        ->where('debit', 460 * 100)
        ->first();

    expect($accountsReceivableEntry)->not->toBeNull();
    expect($cashEntry)->not->toBeNull();

    $invoice->refresh();

    expect($invoice->status)->toBe('Paid');

    // Ensure total debits equal total credits
    $totalDebits = $invoice->journalEntries()->sum('debit');
    $totalCredits = $invoice->journalEntries()->sum('credit');
    expect($totalDebits)->toBe($totalCredits);

    // Assert that the invoice's untaxed_amount is correct
    expect($invoice->untaxed_amount)->toBe(400.0);
});

it('attaches the correct journal entries when an invoice is partially paid without tax', function () {
    $quantity = 2;
    $unitPrice = 200;
    $taxPercent = 0;

    $invoice = TestServices::createInvoice($this->customer, $quantity, $unitPrice, $taxPercent);

    $invoiceItem = TestServices::createInvoiceItem($invoice, $this->product, $quantity, $unitPrice, $taxPercent);

    // Assert that exactly two journal entries are created and attached to the invoice
    expect($invoice->journalEntries()->count())->toBe(2);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsReceivableEntry = $invoice->journalEntries()->where('debit', 400 * 100)->first(); // * 100 CastMoney
    $salesRevenueEntry = $invoice->journalEntries()->where('credit', 400 * 100)->first();

    // Assert Accounts Receivable entry
    expect($accountsReceivableEntry->account_id)->toBe(Account::where('name', 'Accounts Receivable')->first()->id);
    expect($accountsReceivableEntry->debit)->toBe(400.0);

    // Assert Sales Revenue entry
    expect($salesRevenueEntry->account_id)->toBe(Account::where('type', Account::TYPE_REVENUE)->first()->id);
    expect($salesRevenueEntry->credit)->toBe(400.0);

    $currency_id = Currency::where('code', 'USD')->first()->id;

    // Stage 2: Pay the invoice
    $payment = TestServices::createPayment($invoice, 200, 'Cash', 'Income', $currency_id, 1, 200);

    expect($payment->amount)->toBe(200.0);

    // Assert that the transaction has two journal entries
    expect($payment->journalEntries()->count())->toBe(2);

    $journalEntriesCount = JournalEntry::count();
    // at this level there should be 7 records of journal entries
    // 3 journal entries for the bill and it's tax
    // 2 entries for the invoice
    // 2 entries for the payment
    expect($journalEntriesCount)->toBe(7);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsReceivableEntry = $payment->journalEntries()
        ->where('account_id', Account::where('name', 'Accounts Receivable')->first()->id)
        ->where('credit', 200 * 100)
        ->first();

    $cashEntry = $payment->journalEntries()
        ->where('account_id', Account::where('name', 'Cash')->first()->id)
        ->where('debit', 200 * 100)
        ->first();

    expect($accountsReceivableEntry)->not->toBeNull();
    expect($cashEntry)->not->toBeNull();

    $invoice->refresh();

    expect($invoice->status)->toBe('Partial');

    // Ensure total debits equal total credits
    $totalDebits = $invoice->journalEntries()->sum('debit');
    $totalCredits = $invoice->journalEntries()->sum('credit');
    expect($totalDebits)->toBe($totalCredits);

    // Assert that the invoice's untaxed_amount is correct
    expect($invoice->untaxed_amount)->toBe(400.0);
});

it('attaches the correct journal entries when an invoice is partially paid with tax', function () {
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
    $quantity = 2;
    $unitPrice = 200;
    $taxPercent = 15;

    $invoice = TestServices::createInvoice($this->customer, $quantity, $unitPrice, $taxPercent);

    $invoiceItem = TestServices::createInvoiceItem($invoice, $this->product, $quantity, $unitPrice, $taxPercent);

    $tax = Tax::where('name', '15% Sales')->first();

    $invoiceItem->taxes()->attach([
        'tax_id' => $tax->id,
        'tax_amount' => ($quantity * $unitPrice) * $taxPercent,
    ]);

    // Assert that exactly three journal entries are created and attached to the invoice
    expect($invoice->journalEntries()->count())->toBe(3);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsReceivableEntry = $invoice->journalEntries()->where('debit', 460 * 100)->first(); // * 100 CastMoney
    $salesRevenueEntry = $invoice->journalEntries()->where('credit', 400 * 100)->first();
    $taxPayableEntry = $invoice->journalEntries()->where('credit', 60 * 100)->first();

    // Assert Accounts Receivable entry
    expect($accountsReceivableEntry->account_id)->toBe(Account::where('name', 'Accounts Receivable')->first()->id);
    expect($accountsReceivableEntry->debit)->toBe(460.0);

    // Assert Sales Revenue entry
    expect($salesRevenueEntry->account_id)->toBe(Account::where('type', Account::TYPE_REVENUE)->first()->id);
    expect($salesRevenueEntry->credit)->toBe(400.0);

    // Assert Tax Payable entry
    expect($taxPayableEntry->account_id)->toBe(Account::where('name', 'Tax Payable')->first()->id);
    expect($taxPayableEntry->credit)->toBe(60.0);

    $currency_id = Currency::where('code', 'USD')->first()->id;

    // Stage 2: Partially pay the invoice
    $payment = TestServices::createPayment($invoice, 230, 'Cash', 'Income', $currency_id, 1, 230);

    expect($payment->amount)->toBe(230.0);

    // Assert that the transaction has two journal entries
    expect($payment->journalEntries()->count())->toBe(2);

    $journalEntriesCount = JournalEntry::count();
    // at this level there should be 8 records of journal entries
    // 3 journal entries for the bill and it's tax
    // 3 entries for the invoice and it's tax
    // 2 entries for the payment
    expect($journalEntriesCount)->toBe(8);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsReceivableEntry = $payment->journalEntries()
        ->where('account_id', Account::where('name', 'Accounts Receivable')->first()->id)
        ->where('credit', 230 * 100)
        ->first();

    $cashEntry = $payment->journalEntries()
        ->where('account_id', Account::where('name', 'Cash')->first()->id)
        ->where('debit', 230 * 100)
        ->first();

    expect($accountsReceivableEntry)->not->toBeNull();
    expect($cashEntry)->not->toBeNull();

    $invoice->refresh();

    expect($invoice->status)->toBe('Partial');

    // Ensure total debits equal total credits
    $totalDebits = $invoice->journalEntries()->sum('debit');
    $totalCredits = $invoice->journalEntries()->sum('credit');
    expect($totalDebits)->toBe($totalCredits);

    // Assert that the invoice's untaxed_amount is correct
    expect($invoice->untaxed_amount)->toBe(400.0);
});

it('creates an invoice with multiple items and mixed taxes correctly', function () {
    $quantity1 = 2;
    $unitPrice1 = 200;
    $taxPercent1 = 15;

    $quantity2 = 3;
    $unitPrice2 = 100;
    $taxPercent2 = 5;

    $quantity3 = 1;
    $unitPrice3 = 300;
    $taxPercent3 = 0;

    $invoice = TestServices::createInvoice($this->customer, $quantity1, $unitPrice1, $taxPercent1);

    $invoiceItem1 = TestServices::createInvoiceItem($invoice, $this->product, $quantity1, $unitPrice1, $taxPercent1);
    $invoiceItem2 = TestServices::createInvoiceItem($invoice, $this->product, $quantity2, $unitPrice2, $taxPercent2);
    $invoiceItem3 = TestServices::createInvoiceItem($invoice, $this->product, $quantity3, $unitPrice3, $taxPercent3);

    // Refresh the invoice to ensure the total_amount is updated
    $invoice->refresh();

    // Calculate the expected amounts
    $expectedUntaxedAmount = $invoiceItem1->untaxed_amount + $invoiceItem2->untaxed_amount + $invoiceItem3->untaxed_amount;
    $expectedTaxAmount = $invoiceItem1->tax_amount + $invoiceItem2->tax_amount;
    $expectedTotalAmount = $expectedUntaxedAmount + $expectedTaxAmount;

    // Assert that the invoice's amounts are correct
    expect($invoice->untaxed_amount)->toBe($expectedUntaxedAmount);
    expect($invoice->tax_amount)->toBe($expectedTaxAmount);
    expect($invoice->total_amount)->toBe($expectedTotalAmount);
});

it('creates an invoice with five items and mixed taxes correctly', function () {
    $quantity1 = 2;
    $unitPrice1 = 200;
    $taxPercent1 = 15;

    $quantity2 = 3;
    $unitPrice2 = 100;
    $taxPercent2 = 5;

    $quantity3 = 1;
    $unitPrice3 = 300;
    $taxPercent3 = 0;

    $quantity4 = 4;
    $unitPrice4 = 150;
    $taxPercent4 = 10;

    $quantity5 = 5;
    $unitPrice5 = 50;
    $taxPercent5 = 8;

    $invoice = TestServices::createInvoice($this->customer, $quantity1, $unitPrice1, $taxPercent1);

    $invoiceItem1 = TestServices::createInvoiceItem($invoice, $this->product, $quantity1, $unitPrice1, $taxPercent1);
    $invoiceItem2 = TestServices::createInvoiceItem($invoice, $this->product, $quantity2, $unitPrice2, $taxPercent2);
    $invoiceItem3 = TestServices::createInvoiceItem($invoice, $this->product, $quantity3, $unitPrice3, $taxPercent3);
    $invoiceItem4 = TestServices::createInvoiceItem($invoice, $this->product, $quantity4, $unitPrice4, $taxPercent4);
    $invoiceItem5 = TestServices::createInvoiceItem($invoice, $this->product, $quantity5, $unitPrice5, $taxPercent5);

    // Refresh the invoice to ensure the total_amount is updated
    $invoice->refresh();

    // Calculate the expected amounts
    $expectedUntaxedAmount = $invoiceItem1->untaxed_amount + $invoiceItem2->untaxed_amount + $invoiceItem3->untaxed_amount + $invoiceItem4->untaxed_amount + $invoiceItem5->untaxed_amount;
    $expectedTaxAmount = $invoiceItem1->tax_amount + $invoiceItem2->tax_amount + $invoiceItem4->tax_amount + $invoiceItem5->tax_amount;
    $expectedTotalAmount = $expectedUntaxedAmount + $expectedTaxAmount;

    // Assert that the invoice's amounts are correct
    expect($invoice->untaxed_amount)->toBe($expectedUntaxedAmount);
    expect($invoice->tax_amount)->toBe($expectedTaxAmount);
    expect($invoice->total_amount)->toBe($expectedTotalAmount);
});

it('attaches the correct journal entries when an invoice is partially paid with two payments in different currencies', function () {
    $quantity = 2;
    $unitPrice = 200;
    $taxPercent = 0;

    $invoice = TestServices::createInvoice($this->customer, $quantity, $unitPrice, $taxPercent);

    $invoiceItem = TestServices::createInvoiceItem($invoice, $this->product, $quantity, $unitPrice, $taxPercent);

    // Assert that exactly two journal entries are created and attached to the invoice
    expect($invoice->journalEntries()->count())->toBe(2);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsReceivableEntry = $invoice->journalEntries()->where('debit', 400 * 100)->first(); // * 100 CastMoney
    $salesRevenueEntry = $invoice->journalEntries()->where('credit', 400 * 100)->first();

    // Assert Accounts Receivable entry
    expect($accountsReceivableEntry->account_id)->toBe(Account::where('name', 'Accounts Receivable')->first()->id);
    expect($accountsReceivableEntry->debit)->toBe(400.0);

    // Assert Sales Revenue entry
    expect($salesRevenueEntry->account_id)->toBe(Account::where('type', Account::TYPE_REVENUE)->first()->id);
    expect($salesRevenueEntry->credit)->toBe(400.0);

    // Stage 2: Make first payment in USD
    $usdCurrencyId = Currency::where('code', 'USD')->first()->id;
    $usdPaymentAmount = 150; // $150
    $exchangeRateUSD = 1; // $1 = 1 USD
    $paymentUSD = TestServices::createPayment($invoice, $usdPaymentAmount, 'Cash', 'Income', $usdCurrencyId, $exchangeRateUSD, $usdPaymentAmount);

    expect($paymentUSD->amount)->toBe(150.0);

    $exchangeRateEUR = ExchangeRate::create([
        'base_currency_id' => Currency::where('code', 'USD')->first()->id,
        'target_currency_id' => Currency::where('code', 'EUR')->first()->id,
        'rate' => 0.96,
    ]);

    // Stage 3: Make second payment in EUR
    $eurCurrencyId = Currency::where('code', 'EUR')->first()->id;
    $eurPaymentAmount = 220; // €220

    $paymentEUR = TestServices::createPayment($invoice, $eurPaymentAmount, 'Cash', 'Income', $eurCurrencyId, $exchangeRateEUR->rate, $eurPaymentAmount / $exchangeRateEUR->rate);

    expect($paymentEUR->amount_in_document_currency)->toBe(229.0);

    // Assert that both payments created journal entries
    expect($paymentUSD->journalEntries()->count())->toBe(2);
    expect($paymentEUR->journalEntries()->count())->toBe(2);

    // Assert journal entries for first payment
    $usdReceivableEntry = $paymentUSD->journalEntries()
        ->where('account_id', Account::where('name', 'Accounts Receivable')->first()->id)
        ->where('credit', $usdPaymentAmount * 100)
        ->first();

    $usdCashEntry = $paymentUSD->journalEntries()
        ->where('account_id', Account::where('name', 'Cash')->first()->id)
        ->where('debit', $usdPaymentAmount * 100)
        ->first();

    expect($usdReceivableEntry)->not->toBeNull();
    expect($usdCashEntry)->not->toBeNull();

    // Assert journal entries for second payment
    $eurReceivableEntry = $paymentEUR->journalEntries()
        ->where('account_id', Account::where('name', 'Accounts Receivable')->first()->id)
        ->where('credit', 229.0 * 100)
        ->first();

    $eurCashEntry = $paymentEUR->journalEntries()
        ->where('account_id', Account::where('name', 'Cash')->first()->id)
        ->where('debit', 229.0 * 100)
        ->first();

    expect($eurReceivableEntry)->not->toBeNull();
    expect($eurCashEntry)->not->toBeNull();

    $invoice->refresh();

    expect($invoice->status)->toBe('Partial');

    // Ensure total debits equal total credits
    $totalDebits = $invoice->journalEntries()->sum('debit');
    $totalCredits = $invoice->journalEntries()->sum('credit');
    expect($totalDebits)->toBe($totalCredits);

    // Assert that the invoice's untaxed_amount is correct
    expect($invoice->untaxed_amount)->toBe(400.0);
});

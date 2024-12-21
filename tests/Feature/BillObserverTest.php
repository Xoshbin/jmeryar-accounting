<?php

use Xoshbin\JmeryarAccounting\Database\Seeders\DatabaseSeeder;
use Xoshbin\JmeryarAccounting\Models\Bill;
use Xoshbin\JmeryarAccounting\Models\BillItem;
use Xoshbin\JmeryarAccounting\Models\Supplier;
use Xoshbin\JmeryarAccounting\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\Currency;
use Xoshbin\JmeryarAccounting\Models\JournalEntry;
use Xoshbin\JmeryarAccounting\Models\Tax;

/**
 * The journal entries for a bill are recorded in two stages:
 *
 * Stage 1: When the bill is received
 * | Date       | Account              | Debit  | Credit |
 * |------------|----------------------|--------|--------|
 * | YYYY-MM-DD | Expense Account      | Amount |        | 
 * | YYYY-MM-DD | Tax Payable Account  | Amount |        |
 * | YYYY-MM-DD | Accounts Payable     |        | Amount | 
 *
 * Stage 2: When the bill is paid
 * | Date       | Account              | Debit  | Credit |
 * |------------|----------------------|--------|--------|
 * | YYYY-MM-DD | Accounts Payable     | Amount |        |
 * | YYYY-MM-DD | Cash/Bank Account    |        | Amount | 
 *
 * Explanation:
 * - Accounts Payable: Credited when the bill is received (liability), debited when paid.
 * - Expense Account: Debited with the specific expense amount (e.g., "Rent Expense").
 * - Tax Payable Account: Debited with the tax amount.
 * - Cash/Bank Account: Credited when the bill is paid, reducing the balance.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->supplier = Supplier::factory()->create();
    $this->product = Product::factory()->create();
});


it('attaches journal entries to the bill', function () {
    // Create a bill
    $bill = Bill::factory()->create(['supplier_id' => $this->supplier->id]);

    // Create bill items
    BillItem::factory()->create([
        'bill_id' => $bill->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'cost_price' => 100,
        'unit_price' => 200,
        'total_cost' => 400, // 2 * 200
        'tax_amount' => 0,
        'untaxed_amount' => 400,
    ]);

    // Assert that journal entries are created and attached to the bill
    expect($bill->journalEntries()->count())->toBe(2);
});

it('restores inventory to the correct batches when a (bill item) is deleted', function () {

    // Create a bill with an item
    $bill = Bill::factory()->create(['supplier_id' => $this->supplier->id]);

    // Create two bill items with different batches
    $billItem = BillItem::factory()->create([
        'bill_id' => $bill->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'cost_price' => 100,
        'unit_price' => 200,
        'total_cost' => 200, // 2 * 100
        'tax_amount' => 0,
        'untaxed_amount' => 200,
    ]);

    $batch = $this->product->inventoryBatches()->oldest()->first();

    expect($batch->quantity)->toBe(2);
    expect($batch->quantity)->not()->toBe(0);

    // Delete the bill items
    $billItem->delete();

    $newBatchAfterDelete = $this->product->inventoryBatches()->oldest()->first();

    // Assert that the batch quantities are restored
    expect($newBatchAfterDelete)->toBeNull();
});

it('restores inventory to the correct batches when an (bill) is deleted', function () {

    // Create a bill with an item
    $bill = Bill::factory()->create(['supplier_id' => $this->supplier->id]);

    // Create two bill items with different batches
    $billItem = BillItem::factory()->create([
        'bill_id' => $bill->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'cost_price' => 100,
        'unit_price' => 200,
        'total_cost' => 200, // 1 * 200
        'tax_amount' => 0,
        'untaxed_amount' => 200,
    ]);

    $batch = $this->product->inventoryBatches()->oldest()->first();

    expect($batch->quantity)->toBe(2);
    expect($batch->quantity)->not()->toBe(0);

    // Delete the bill items
    $bill->delete();

    $newBatchAfterDelete = $this->product->inventoryBatches()->oldest()->first();

    // Assert that the batch quantities are restored
    expect($newBatchAfterDelete)->toBeNull();
});

it('attaches the correct journal entries to the bill', function () {
    // Create a bill with an item
    $bill = Bill::factory()->create([
        'supplier_id' => $this->supplier->id,
        'total_amount' => 220, // Includes tax
        'tax_amount' => 20,
        'untaxed_amount' => 200,
    ]);

    BillItem::factory()->create([
        'bill_id' => $bill->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'cost_price' => 100,
        'unit_price' => 200,
        'total_cost' => 200, // 2 * 200
        'tax_amount' => 20,
        'untaxed_amount' => 200,
    ]);

    // Assert that three journal entries are created and attached to the bill
    expect($bill->journalEntries()->count())->toBe(3);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsPayableEntry = $bill->journalEntries()->where('credit', 220 * 100)->first(); // * 100 CastMoney
    $expenseEntry = $bill->journalEntries()->where('debit', 200 * 100)->first();
    $taxPayableEntry = $bill->journalEntries()->where('debit', 20 * 100)->first();

    /**
     * The journal entries for a bill are recorded as follows:
     *
     * | Date       | Account              | Debit  | Credit |
     * |------------|----------------------|--------|--------|
     * | YYYY-MM-DD | Accounts Payable     |        | 220    |
     * | YYYY-MM-DD | Expense Account      | 200    |        |
     * | YYYY-MM-DD | Tax Payable Account  | 20     |        |
     *
     * Explanation:
     * - Accounts Payable: Credited with the total bill amount (220), indicating a liability.
     * - Expense Account: Debited with the untaxed amount (200), representing the expense incurred.
     * - Tax Payable Account: Debited with the tax amount (20), representing the tax liability.
     */

    // Assert Accounts Payable entry
    expect($accountsPayableEntry->account_id)->toBe(Account::where('type', Account::TYPE_LIABILITY)->first()->id);
    expect($accountsPayableEntry->credit)->toBe(220.0);

    // Assert Expense entry
    expect($expenseEntry->account_id)->toBe(Account::where('type', Account::TYPE_EXPENSE)->first()->id);
    expect($expenseEntry->debit)->toBe(200.0);

    // Assert Tax Payable entry
    expect($taxPayableEntry->account_id)->toBe(Account::where('name', 'Tax Payable')->first()->id);
    expect($taxPayableEntry->debit)->toBe(20.0);

    // Ensure total debits equal total credits
    $totalDebits = $bill->journalEntries()->sum('debit');
    $totalCredits = $bill->journalEntries()->sum('credit');
    expect($totalDebits)->toBe($totalCredits);
});

it('attaches two journal entries to the bill when there is no tax', function () {
    // Create a bill with no tax
    $bill = Bill::factory()->create([
        'supplier_id' => $this->supplier->id,
        'total_amount' => 200,
        'untaxed_amount' => 200,
    ]);
    BillItem::factory()->create([
        'bill_id' => $bill->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'cost_price' => 100,
        'unit_price' => 200,
        'total_cost' => 200, // 2 * 200
        'tax_amount' => 0, // No tax
        'untaxed_amount' => 200,
    ]);

    // Assert that exactly two journal entries are created and attached to the bill
    expect($bill->journalEntries()->count())->toBe(2);
});

it('attaches three journal entries to the bill when there is tax', function () {
    // Create a bill with tax
    $bill = Bill::factory()->create([
        'supplier_id' => $this->supplier->id,
        'tax_amount' => (2 * 100) * 0.10 // add tax amount
    ]);

    BillItem::factory()->create([
        'bill_id' => $bill->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'cost_price' => 100,
        'unit_price' => 200,
        'total_cost' => 400, // 2 * 200
        'tax_amount' => 10, // With tax
        'untaxed_amount' => 400,
    ]);

    // Assert that exactly three journal entries are created and attached to the bill
    expect($bill->journalEntries()->count())->toBe(3);
});

it('updates inventory and journal entries when a bill item quantity is updated', function () {
    $bill = Bill::factory()->create([
        'supplier_id' => $this->supplier->id,
    ]);

    $billItem = BillItem::factory()->create([
        'bill_id' => $bill->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'cost_price' => 100,
        'unit_price' => 200,
        'total_cost' => 200, // 1 * 200
        'tax_amount' => 0,
        'untaxed_amount' => 200,
    ]);

    expect($bill->journalEntries()->count())->toBe(2);

    $billItem->quantity = 4; // Increase quantity
    $billItem->total_cost = 800; // Increase total_cost 4 * 200
    $billItem->untaxed_amount = 800; // Increase quantity
    $billItem->save();

    // Refresh the bill to ensure the total_amount is updated
    $bill->refresh();


    expect($billItem->total_cost)->toBe(800.0);
    expect($billItem->untaxed_amount)->toBe(800.0);

    $bill->total_amount = 800;
    $bill->untaxed_amount = 800;
    $bill->save();

    // Assert that the journal entries are updated (You'll need to add assertions for specific journal entry values)
    expect($bill->journalEntries()->count())->toBe(2);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsPayableEntry = $bill->journalEntries()->where('credit', '>', 0)->first(); // * 100 CastMoney
    $expenseEntry = $bill->journalEntries()->where('debit', 800 * 100)->first();

    /**
     * The journal entries for a bill are recorded as follows:
     *
     * | Date       | Account              | Debit  | Credit |
     * |------------|----------------------|--------|--------|
     * | YYYY-MM-DD | Accounts Payable     |        | 800    |
     * | YYYY-MM-DD | Expense Account      | 800    |        |
     * | YYYY-MM-DD | Tax Payable Account  | 0     |        | // in this text no tax is recorded
     *
     * Explanation:
     * - Accounts Payable: Credited with the total bill amount (220), indicating a liability.
     * - Expense Account: Debited with the untaxed amount (200), representing the expense incurred.
     * - Tax Payable Account: Debited with the tax amount (20), representing the tax liability.
     */

    // Assert Accounts Payable entry
    expect($accountsPayableEntry->account_id)->toBe(Account::where('type', Account::TYPE_LIABILITY)->first()->id);
    expect($accountsPayableEntry->credit)->toBe(800.0);

    // Assert Expense entry
    expect($expenseEntry->account_id)->toBe(Account::where('type', Account::TYPE_EXPENSE)->first()->id);
    expect($expenseEntry->debit)->toBe(800.0);

    // Ensure total debits equal total credits
    $totalDebits = $bill->journalEntries()->sum('debit');
    $totalCredits = $bill->journalEntries()->sum('credit');
    expect($totalDebits)->toBe($totalCredits);
});


it('deletes taxes when an bill item is deleted', function () {
    $bill = Bill::factory()->create([
        'supplier_id' => $this->supplier->id,
        'tax_amount' => (4 * 100) * 0.10, // add tax amount
        'total_amount' => 440,
        'untaxed_amount' => 400,
    ]);

    $billItem = BillItem::factory()->create([
        'bill_id' => $bill->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'cost_price' => 100,
        'unit_price' => 200,
        'total_cost' => 440, // 2 * 200
        'tax_amount' => 40, // With tax
        'untaxed_amount' => 400,
    ]);

    $tax = Tax::where('name', '15% Sales')->first();

    $billItem->taxes()->attach([
        'tax_id' => $tax->id,
        'tax_amount' => (2 * 100) * 0.10
    ]);

    $billItem->delete();

    expect($billItem->taxes()->count())->toBe(0);
});

it('deletes taxes when an bill is deleted', function () {
    $bill = Bill::factory()->create([
        'supplier_id' => $this->supplier->id,
        'tax_amount' => (4 * 100) * 0.10, // add tax amount
        'total_amount' => 440,
        'untaxed_amount' => 400,
    ]);

    $billItem = BillItem::factory()->create([
        'bill_id' => $bill->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'cost_price' => 100,
        'unit_price' => 200,
        'total_cost' => 440, // 2 * 200
        'tax_amount' => 40, // With tax
        'untaxed_amount' => 400,
    ]);

    $tax = Tax::where('name', '15% Sales')->first();

    $billItem->taxes()->attach([
        'tax_id' => $tax->id,
        'tax_amount' => (2 * 100) * 0.10
    ]);

    $bill->delete();

    expect($billItem->taxes()->count())->toBe(0);
});

it('calculates taxes correctly for multiple bill items with the same product but different prices and taxes', function () {
    // Create a bill
    $bill = Bill::factory()->create([
        'supplier_id' => $this->supplier->id
    ]);

    $billItem1 = BillItem::factory()->create([
        'bill_id' => $bill->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'cost_price' => 100,
        'total_cost' => 2 * 100 + (2 * 100 * (15 / 100)), // 2 * 100
        'tax_amount' => 2 * 100 * (15 / 100), // With tax
        'untaxed_amount' => 2 * 100,
    ]);

    $billItem1->taxes()->attach(1, ['tax_amount' => 2 * 100 * (15 / 100)]);

    $billItem2 = BillItem::factory()->create([
        'bill_id' => $bill->id,
        'product_id' => $this->product->id,
        'quantity' => 3,
        'cost_price' => 50,
        'total_cost' => 3 * 50 + (3 * 50 * (5 / 100)), // 3 * 50
        'tax_amount' => 3 * 50 * (5 / 100), // With tax
        'untaxed_amount' => 3 * 50,
    ]);

    $billItem2->taxes()->attach(2, ['tax_amount' => 3 * 50 * (5 / 100)]);

    // Refresh the bill to ensure the total_amount is updated
    $bill->refresh();

    // Calculate the expected total amount
    $expectedTotalAmount = $billItem1->total_cost + $billItem2->total_cost;

    // Assert that the bill's total_amount is correct
    $this->assertEquals($expectedTotalAmount, $bill->total_amount);
});

it('calculates the untaxed amount of the bill correctly', function () {
    // Create a bill
    $bill = Bill::factory()->create([
        'supplier_id' => $this->supplier->id
    ]);

    $billItem1 = BillItem::factory()->create([
        'bill_id' => $bill->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'cost_price' => 100,
        'total_cost' => 2 * 100 + (2 * 100 * (15 / 100)), // 2 * 100
        'tax_amount' => 2 * 100 * (15 / 100), // With tax
        'untaxed_amount' => 2 * 100,
    ]);

    $billItem1->taxes()->attach(1, ['tax_amount' => 2 * 100 * (15 / 100)]);

    $billItem2 = BillItem::factory()->create([
        'bill_id' => $bill->id,
        'product_id' => $this->product->id,
        'quantity' => 3,
        'cost_price' => 50,
        'total_cost' => 3 * 50 + (3 * 50 * (5 / 100)), // 3 * 50
        'tax_amount' => 3 * 50 * (5 / 100), // With tax
        'untaxed_amount' => 3 * 50,
    ]);

    $billItem2->taxes()->attach(2, ['tax_amount' => 3 * 50 * (5 / 100)]);

    // Refresh the bill to ensure the total_amount is updated
    $bill->refresh();

    // Calculate the expected untaxed amount
    $expectedUntaxedAmount = $billItem1->untaxed_amount + $billItem2->untaxed_amount;

    // Assert that the bill's untaxed_amount is correct
    $this->assertEquals($expectedUntaxedAmount, $bill->untaxed_amount);
});

it('attaches the correct journal entries when a bill is paid without tax', function () {

    /**
     * The journal entries for a bill are recorded in two stages:
     *
     * Stage 1: When the bill is received
     * | Date       | Account              | Debit  | Credit |
     * |------------|----------------------|--------|--------|
     * | YYYY-MM-DD | Expense Account      | 200    |        | 
     * | YYYY-MM-DD | Tax Payable Account  | 200    |        |
     * | YYYY-MM-DD | Accounts Payable     |        | 200    | 
     *
     * Stage 2: When the bill is paid
     * | Date       | Account              | Debit  | Credit |
     * |------------|----------------------|--------|--------|
     * | YYYY-MM-DD | Accounts Payable     | 200    |        |
     * | YYYY-MM-DD | Cash/Bank Account    |        | 200    | 
     *
     * Explanation:
     * - Accounts Payable: Credited when the bill is received (liability), debited when paid.
     * - Expense Account: Debited with the specific expense amount (e.g., "Rent Expense").
     * - Tax Payable Account: Debited with the tax amount.
     * - Cash/Bank Account: Credited when the bill is paid, reducing the balance.
     */

    // Create a bill with no tax
    $bill = Bill::factory()->create([
        'supplier_id' => $this->supplier->id,
        'total_amount' => 200,
        'untaxed_amount' => 200,
    ]);

    BillItem::factory()->create([
        'bill_id' => $bill->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'cost_price' => 100,
        'unit_price' => 200,
        'total_cost' => 200, // 2 * 100
        'tax_amount' => 0, // No tax
        'untaxed_amount' => 200,
    ]);

    // Assert that exactly two journal entries are created and attached to the bill
    expect($bill->journalEntries()->count())->toBe(2);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsPayableEntry = $bill->journalEntries()->where('credit', 200 * 100)->first(); // * 100 CastMoney
    $expenseEntry = $bill->journalEntries()->where('debit', 200 * 100)->first();

    // Assert Accounts Payable entry
    expect($accountsPayableEntry->account_id)->toBe(Account::where('type', Account::TYPE_LIABILITY)->first()->id);
    expect($accountsPayableEntry->credit)->toBe(200.0);

    // Assert Expense entry
    expect($expenseEntry->account_id)->toBe(Account::where('type', Account::TYPE_EXPENSE)->first()->id);
    expect($expenseEntry->debit)->toBe(200.0);

    // Stage 2: Pay the bill
    $payment = $bill->payments()->create([
        'amount' => 200,
        'payment_date' => now(),
        'payment_method' => 'Cash',
        'payment_type' => 'Expense',
        'currency_id' => Currency::where('code', 'USD')->first()->id,
        'exchange_rate' => 1,
        'amount_in_invoice_currency' => 200,
    ]);

    // Assert that the transaction has two journal entries
    expect($payment->journalEntries()->count())->toBe(2);

    $journalEntriesCount = JournalEntry::count();
    // at this level there should be 5 records of journal entries
    expect($journalEntriesCount)->toBe(4);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsPayableEntry = $payment->journalEntries()
        ->where('account_id', Account::where('name', 'Accounts Payable')->first()->id)
        ->where('debit', 200 * 100)
        ->first();

    $cashEntry = $payment->journalEntries()
        ->where('account_id', Account::where('name', 'Cash')->first()->id)
        ->where('credit', 200 * 100)
        ->first();

    expect($accountsPayableEntry)->not->toBeNull();
    expect($cashEntry)->not->toBeNull();

    $bill->refresh();

    expect($bill->status)->toBe('Paid');

    // Ensure total debits equal total credits
    $totalDebits = $bill->journalEntries()->sum('debit');
    $totalCredits = $bill->journalEntries()->sum('credit');
    expect($totalDebits)->toBe($totalCredits);
});

it('attaches the correct journal entries when a bill is paid with tax', function () {

    /**
     * The journal entries for a bill are recorded in two stages:
     *
     * Stage 1: When the bill is received
     * | Date       | Account              | Debit  | Credit |
     * |------------|----------------------|--------|--------|
     * | YYYY-MM-DD | Expense Account      | Amount |        | 
     * | YYYY-MM-DD | Tax Payable Account  | Amount |        |
     * | YYYY-MM-DD | Accounts Payable     |        | Amount | 
     *
     * Stage 2: When the bill is paid
     * | Date       | Account              | Debit  | Credit |
     * |------------|----------------------|--------|--------|
     * | YYYY-MM-DD | Accounts Payable     | Amount |        |
     * | YYYY-MM-DD | Cash/Bank Account    |        | Amount | 
     *
     * Explanation:
     * - Accounts Payable: Credited when the bill is received (liability), debited when paid.
     * - Expense Account: Debited with the specific expense amount (e.g., "Rent Expense").
     * - Tax Payable Account: Debited with the tax amount.
     * - Cash/Bank Account: Credited when the bill is paid, reducing the balance.
     */


    // Create a bill with tax
    $bill = Bill::factory()->create([
        'supplier_id' => $this->supplier->id,
        'total_amount' => 220, // Includes tax
        'tax_amount' => 20,
        'untaxed_amount' => 200,
    ]);

    BillItem::factory()->create([
        'bill_id' => $bill->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'cost_price' => 100,
        'unit_price' => 200,
        'total_cost' => 220, // (2 * 100) + 20
        'tax_amount' => 20,
        'untaxed_amount' => 200,
    ]);

    // Assert that exactly three journal entries are created and attached to the bill
    expect($bill->journalEntries()->count())->toBe(3);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsPayableEntry = $bill->journalEntries()->where('credit', 220 * 100)->first(); // * 100 CastMoney
    $expenseEntry = $bill->journalEntries()->where('debit', 200 * 100)->first();
    $taxPayableEntry = $bill->journalEntries()->where('debit', 20 * 100)->first();

    // Assert Accounts Payable entry
    expect($accountsPayableEntry->account_id)->toBe(Account::where('type', Account::TYPE_LIABILITY)->first()->id);
    expect($accountsPayableEntry->credit)->toBe(220.0);

    // Assert Expense entry
    expect($expenseEntry->account_id)->toBe(Account::where('type', Account::TYPE_EXPENSE)->first()->id);
    expect($expenseEntry->debit)->toBe(200.0);

    // Assert Tax Payable entry
    expect($taxPayableEntry->account_id)->toBe(Account::where('name', 'Tax Payable')->first()->id);
    expect($taxPayableEntry->debit)->toBe(20.0);


    // stage 2: Pay the bill

    $payment = $bill->payments()->create([
        'amount' => 220,
        'payment_date' => now(),
        'payment_method' => 'Cash',
        'payment_type' => 'Expense',
        'currency_id' => Currency::where('code', 'USD')->first()->id,
        'exchange_rate' => 1,
        'amount_in_invoice_currency' => 200,
    ]);

    expect($payment->journalEntries()->count())->toBe(2);
    expect($payment->amount)->toBe(220.0);

    $journalEntriesCount = JournalEntry::count();

    // at this level there should be 5 records of journal entries
    expect($journalEntriesCount)->toBe(5);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsPayableEntry = $payment->journalEntries()
        ->where('account_id', Account::where('name', 'Accounts Payable')->first()->id)
        ->where('debit', 220 * 100)
        ->first();

    $cashEntry = $payment->journalEntries()
        ->where('account_id', Account::where('name', 'Cash')->first()->id)
        ->where('credit', 220 * 100)
        ->first();

    expect($accountsPayableEntry)->not->toBeNull();
    expect($cashEntry)->not->toBeNull();

    $bill->refresh();

    expect($bill->status)->toBe('Paid');

    // Ensure total debits equal total credits
    $totalDebits = $bill->journalEntries()->sum('debit');
    $totalCredits = $bill->journalEntries()->sum('credit');
    expect($totalDebits)->toBe($totalCredits);
});

it('attaches the correct journal entries when a bill is partially paid without tax', function () {
    // Create a bill with no tax
    $bill = Bill::factory()->create([
        'supplier_id' => $this->supplier->id,
        'total_amount' => 200,
        'untaxed_amount' => 200,
    ]);

    BillItem::factory()->create([
        'bill_id' => $bill->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'cost_price' => 100,
        'unit_price' => 200,
        'total_cost' => 200, // 2 * 100
        'tax_amount' => 0, // No tax
        'untaxed_amount' => 200,
    ]);

    // Assert that exactly two journal entries are created and attached to the bill
    expect($bill->journalEntries()->count())->toBe(2);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsPayableEntry = $bill->journalEntries()->where('credit', 200 * 100)->first(); // * 100 CastMoney
    $expenseEntry = $bill->journalEntries()->where('debit', 200 * 100)->first();

    // Assert Accounts Payable entry
    expect($accountsPayableEntry->account_id)->toBe(Account::where('type', Account::TYPE_LIABILITY)->first()->id);
    expect($accountsPayableEntry->credit)->toBe(200.0);

    // Assert Expense entry
    expect($expenseEntry->account_id)->toBe(Account::where('type', Account::TYPE_EXPENSE)->first()->id);
    expect($expenseEntry->debit)->toBe(200.0);

    // Partial payment
    $payment = $bill->payments()->create([
        'amount' => 100,
        'payment_date' => now(),
        'payment_method' => 'Cash',
        'payment_type' => 'Expense',
        'currency_id' => Currency::where('code', 'USD')->first()->id,
        'exchange_rate' => 1,
        'amount_in_invoice_currency' => 200,
    ]);

    expect($payment->journalEntries()->count())->toBe(2);
    expect($payment->amount)->toBe(100.0);

    $journalEntriesCount = JournalEntry::count();
    // at this level there should be 5 records of journal entries
    expect($journalEntriesCount)->toBe(4);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsPayableEntry = $payment->journalEntries()
        ->where('account_id', Account::where('name', 'Accounts Payable')->first()->id)
        ->where('debit', 100 * 100)
        ->first();

    $cashEntry = $payment->journalEntries()
        ->where('account_id', Account::where('name', 'Cash')->first()->id)
        ->where('credit', 100 * 100)
        ->first();

    expect($accountsPayableEntry)->not->toBeNull();
    expect($cashEntry)->not->toBeNull();

    $bill->refresh();

    expect($bill->status)->toBe('Partial');

    // Ensure total debits equal total credits
    $totalDebits = $bill->journalEntries()->sum('debit');
    $totalCredits = $bill->journalEntries()->sum('credit');
    expect($totalDebits)->toBe($totalCredits);
});

it('attaches the correct journal entries when a bill is partially paid with tax', function () {
    // Create a bill with tax
    $bill = Bill::factory()->create([
        'supplier_id' => $this->supplier->id,
        'total_amount' => 220, // Includes tax
        'tax_amount' => 20,
        'untaxed_amount' => 200,
    ]);

    BillItem::factory()->create([
        'bill_id' => $bill->id,
        'product_id' => $this->product->id,
        'quantity' => 2,
        'cost_price' => 100,
        'unit_price' => 200,
        'total_cost' => 220, // (2 * 100) + 20
        'tax_amount' => 20,
        'untaxed_amount' => 200,
    ]);

    // Assert that exactly three journal entries are created and attached to the bill
    expect($bill->journalEntries()->count())->toBe(3);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsPayableEntry = $bill->journalEntries()->where('credit', 220 * 100)->first(); // * 100 CastMoney
    $expenseEntry = $bill->journalEntries()->where('debit', 200 * 100)->first();
    $taxPayableEntry = $bill->journalEntries()->where('debit', 20 * 100)->first();

    // Assert Accounts Payable entry
    expect($accountsPayableEntry->account_id)->toBe(Account::where('type', Account::TYPE_LIABILITY)->first()->id);
    expect($accountsPayableEntry->credit)->toBe(220.0);

    // Assert Expense entry
    expect($expenseEntry->account_id)->toBe(Account::where('type', Account::TYPE_EXPENSE)->first()->id);
    expect($expenseEntry->debit)->toBe(200.0);

    // Assert Tax Payable entry
    expect($taxPayableEntry->account_id)->toBe(Account::where('name', 'Tax Payable')->first()->id);
    expect($taxPayableEntry->debit)->toBe(20.0);

    // Partial payment
    $payment = $bill->payments()->create([
        'amount' => 110,
        'payment_date' => now(),
        'payment_method' => 'Cash',
        'payment_type' => 'Expense',
        'currency_id' => Currency::where('code', 'USD')->first()->id,
        'exchange_rate' => 1,
        'amount_in_invoice_currency' => 110,
    ]);

    expect($payment->journalEntries()->count())->toBe(2);
    expect($payment->amount)->toBe(110.0);

    $journalEntriesCount = JournalEntry::count();
    // at this level there should be 5 records of journal entries
    expect($journalEntriesCount)->toBe(5);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsPayableEntry = $payment->journalEntries()
        ->where('account_id', Account::where('name', 'Accounts Payable')->first()->id)
        ->where('debit', 110 * 100)
        ->first();

    $cashEntry = $payment->journalEntries()
        ->where('account_id', Account::where('name', 'Cash')->first()->id)
        ->where('credit', 110 * 100)
        ->first();

    expect($accountsPayableEntry)->not->toBeNull();
    expect($cashEntry)->not->toBeNull();

    $bill->refresh();

    expect($bill->status)->toBe('Partial');

    // Ensure total debits equal total credits
    $totalDebits = $bill->journalEntries()->sum('debit');
    $totalCredits = $bill->journalEntries()->sum('credit');
    expect($totalDebits)->toBe($totalCredits);
});

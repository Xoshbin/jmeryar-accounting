<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Services\TestServices;
use Xoshbin\JmeryarAccounting\Database\Seeders\DatabaseSeeder;
use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\Currency;
use Xoshbin\JmeryarAccounting\Models\JournalEntry;
use Xoshbin\JmeryarAccounting\Models\Payment;
use Xoshbin\JmeryarAccounting\Models\Product;
use Xoshbin\JmeryarAccounting\Models\Supplier;
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

it('creates a bill with correct attributes', function () {
    $quantity = 2;
    $costPrice = 100;
    $taxPercent = 15;

    $bill = TestServices::createBill($this->supplier, $quantity, $costPrice, $taxPercent);

    // Assert that the bill has the correct attributes
    expect($bill->supplier_id)->toBe($this->supplier->id);
    expect($bill->total_amount)->toBe(230.0);
    expect($bill->tax_amount)->toBe(30.0);
    expect($bill->untaxed_amount)->toBe(200.0);
});

it('creates a bill without tax correctly', function () {
    $quantity = 2;
    $costPrice = 100;
    $taxPercent = 0;

    $bill = TestServices::createBill($this->supplier, $quantity, $costPrice, $taxPercent);

    // Assert that the bill has the correct attributes
    expect($bill->supplier_id)->toBe($this->supplier->id);
    expect($bill->total_amount)->toBe(200.0);
    expect($bill->tax_amount)->toBe(0.0);
    expect($bill->untaxed_amount)->toBe(200.0);
});

it('creates a bill with multiple items correctly', function () {
    $quantity1 = 2;
    $costPrice1 = 100;
    $taxPercent1 = 15;

    $quantity2 = 3;
    $costPrice2 = 50;
    $taxPercent2 = 5;

    $bill = TestServices::createBill($this->supplier, $quantity1, $costPrice1, $taxPercent1);

    $billItem1 = TestServices::createBillItem($bill, $this->product, $quantity1, $costPrice1, 150, $taxPercent1);
    $billItem2 = TestServices::createBillItem($bill, $this->product, $quantity2, $costPrice2, 125, $taxPercent2);

    // Refresh the bill to ensure the total_amount is updated
    $bill->refresh();

    // Calculate the expected total amount
    $expectedTotalAmount = $billItem1->total_cost + $billItem2->total_cost;
    $expectedTaxAmount = $billItem1->tax_amount + $billItem2->tax_amount;

    echo $expectedTaxAmount;

    // Assert that the bill's total_amount is correct
    expect($bill->total_amount)->toBe($expectedTotalAmount);
    expect($bill->untaxed_amount)->toBe($billItem1->untaxed_amount + $billItem2->untaxed_amount);
    expect($bill->tax_amount)->toBe($expectedTaxAmount);
});

it('attaches journal entries to the bill', function () {
    $quantity = 2;
    $costPrice = 100;
    $taxPercent = 0;

    $bill = TestServices::createBill($this->supplier, $quantity, $costPrice, $taxPercent);

    $billItem = TestServices::createBillItem($bill, $this->product, $quantity, $costPrice, $taxPercent);

    // Assert that journal entries are created and attached to the bill
    expect($bill->journalEntries()->count())->toBe(2);

    // Assert that the bill's untaxed_amount is correct
    expect($bill->untaxed_amount)->toBe(200.0);
    expect($bill->total_amount)->toBe(200.0);
});

it('restores inventory to the correct batches when a (bill item) is deleted', function () {
    $quantity = 2;
    $costPrice = 100;
    $taxPercent = 0;

    $bill = TestServices::createBill($this->supplier, $quantity, $costPrice, $taxPercent);

    $billItem = TestServices::createBillItem($bill, $this->product, $quantity, $costPrice, $taxPercent);

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
    $quantity = 2;
    $costPrice = 100;
    $taxPercent = 0;

    $bill = TestServices::createBill($this->supplier, $quantity, $costPrice, $taxPercent);

    $billItem = TestServices::createBillItem($bill, $this->product, $quantity, $costPrice, $taxPercent);

    $batch = $this->product->inventoryBatches()->oldest()->first();

    expect($batch->quantity)->toBe(2);
    expect($batch->quantity)->not()->toBe(0);

    // Delete the bill items
    $bill->delete();

    $newBatchAfterDelete = $this->product->inventoryBatches()->oldest()->first();

    // Assert that the batch quantities are restored
    expect($newBatchAfterDelete)->toBeNull();
});

it('attaches two journal entries to the bill when there is no tax', function () {
    $quantity = 2;
    $costPrice = 100;
    $taxPercent = 0;

    $bill = TestServices::createBill($this->supplier, $quantity, $costPrice, $taxPercent);

    $billItem = TestServices::createBillItem($bill, $this->product, $quantity, $costPrice, $taxPercent);

    // Assert that exactly two journal entries are created and attached to the bill
    expect($bill->journalEntries()->count())->toBe(2);

    // Assert that the bill's untaxed_amount is correct
    expect($bill->untaxed_amount)->toBe(200.0);
});

it('attaches three journal entries to the bill when there is tax', function () {
    $quantity = 2;
    $costPrice = 100;
    $taxPercent = 15;

    $bill = TestServices::createBill($this->supplier, $quantity, $costPrice, $taxPercent);

    $billItem = TestServices::createBillItem($bill, $this->product, $quantity, $costPrice, $taxPercent);

    // Assert that exactly three journal entries are created and attached to the bill
    expect($bill->journalEntries()->count())->toBe(3);

    // Assert that the bill's untaxed_amount is correct
    expect($bill->untaxed_amount)->toBe(200.0);
});

it('deletes taxes when an bill item is deleted', function () {
    $quantity = 2;
    $costPrice = 100;
    $taxPercent = 15;

    $bill = TestServices::createBill($this->supplier, $quantity, $costPrice, $taxPercent);

    $billItem = TestServices::createBillItem($bill, $this->product, $quantity, $costPrice, $taxPercent);

    $tax = Tax::where('name', '15% Sales')->first();

    $billItem->taxes()->attach([
        'tax_id' => $tax->id,
        'tax_amount' => ($quantity * $costPrice) * $taxPercent,
    ]);

    $billItem->delete();

    expect($billItem->taxes()->count())->toBe(0);
});

it('deletes taxes when an bill is deleted', function () {
    $quantity = 2;
    $costPrice = 100;
    $taxPercent = 15;

    $bill = TestServices::createBill($this->supplier, $quantity, $costPrice, $taxPercent);

    $billItem = TestServices::createBillItem($bill, $this->product, $quantity, $costPrice, $taxPercent);

    $tax = Tax::where('name', '15% Sales')->first();

    $billItem->taxes()->attach([
        'tax_id' => $tax->id,
        'tax_amount' => ($quantity * $costPrice) * $taxPercent,
    ]);

    $bill->delete();

    expect($billItem->taxes()->count())->toBe(0);
});

it('calculates the untaxed amount of the bill correctly', function () {
    $quantity = 2;
    $costPrice = 100;
    $taxPercent = 15;

    $bill = TestServices::createBill($this->supplier, $quantity, $costPrice, $taxPercent);

    $billItem = TestServices::createBillItem($bill, $this->product, $quantity, $costPrice, $taxPercent);

    $tax = Tax::where('name', '15% Sales')->first();

    $billItem->taxes()->attach([
        'tax_id' => $tax->id,
        'tax_amount' => ($quantity * $costPrice) * $taxPercent,
    ]);

    $billItem2 = TestServices::createBillItem($bill, $this->product, 3, 50, $taxPercent);

    $tax = Tax::where('name', '5% Sales')->first();

    $billItem2->taxes()->attach(2, ['tax_amount' => 3 * 50 * (5 / 100)]);

    // Refresh the bill to ensure the total_amount is updated
    $bill->refresh();

    // Calculate the expected untaxed amount
    $expectedUntaxedAmount = $billItem->untaxed_amount + $billItem2->untaxed_amount;

    // Assert that the bill's untaxed_amount is correct
    $this->assertEquals($expectedUntaxedAmount, $bill->untaxed_amount);
});

it('attaches the correct journal entries to the bill', function () {
    $quantity = 2;
    $costPrice = 100;
    $taxPercent = 15;

    $bill = TestServices::createBill($this->supplier, $quantity, $costPrice, $taxPercent);

    $billItem = TestServices::createBillItem($bill, $this->product, $quantity, $costPrice, $taxPercent);

    $tax = Tax::where('name', '15% Sales')->first();

    $billItem->taxes()->attach([
        'tax_id' => $tax->id,
        'tax_amount' => ($quantity * $costPrice) * $taxPercent,
    ]);

    /**
     * The journal entries for a bill are recorded as follows:
     *
     * | Date       | Account              | Debit  | Credit |
     * |------------|----------------------|--------|--------|
     * | YYYY-MM-DD | Accounts Payable     |        | 230    |
     * | YYYY-MM-DD | Expense Account      | 200    |        |
     * | YYYY-MM-DD | Tax Payable Account  | 30     |        |
     *
     * Explanation:
     * - Accounts Payable: Credited with the total bill amount (230), indicating a liability.
     * - Expense Account: Debited with the untaxed amount (200), representing the expense incurred.
     * - Tax Payable Account: Debited with the tax amount (30), representing the tax liability.
     */

    // Assert that three journal entries are created and attached to the bill
    expect($bill->journalEntries()->count())->toBe(3);

    expect($bill->total_amount)->toBe(230.0);
    expect($bill->untaxed_amount)->toBe(200.0);
    expect($bill->tax_amount)->toBe(30.0);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsPayableEntry = $bill->journalEntries()->where('credit', 230 * 100)->first(); // * 100 CastMoney
    $expenseEntry = $bill->journalEntries()->where('debit', 200 * 100)->first();
    $taxPayableEntry = $bill->journalEntries()->where('debit', 30 * 100)->first();

    // Assert Accounts Payable entry
    expect($accountsPayableEntry->account_id)->toBe(Account::where('type', Account::TYPE_LIABILITY)->first()->id);
    expect($accountsPayableEntry->credit)->toBe(230.0);

    // Assert Expense entry
    expect($expenseEntry->account_id)->toBe(Account::where('type', Account::TYPE_EXPENSE)->first()->id);
    expect($expenseEntry->debit)->toBe(200.0);

    // Assert Tax Payable entry
    expect($taxPayableEntry->account_id)->toBe(Account::where('name', 'Tax Payable')->first()->id);
    expect($taxPayableEntry->debit)->toBe(30.0);

    // Ensure total debits equal total credits
    $totalDebits = $bill->journalEntries()->sum('debit');
    $totalCredits = $bill->journalEntries()->sum('credit');
    expect($totalDebits)->toBe($totalCredits);

    // Assert that the bill's untaxed_amount is correct
    expect($bill->untaxed_amount)->toBe(200.0);
    expect($bill->total_amount)->toBe(230.0);
});

it('calculates taxes correctly for multiple bill items with the same product but different prices and taxes', function () {
    $quantity = 2;
    $costPrice = 100;
    $taxPercent = 0;

    $bill = TestServices::createBill($this->supplier, $quantity, $costPrice, $taxPercent);

    $billItem = TestServices::createBillItem($bill, $this->product, $quantity, $costPrice, $taxPercent);

    $tax = Tax::where('name', '15% Sales')->first();

    $billItem->taxes()->attach([
        'tax_id' => $tax->id,
        'tax_amount' => ($quantity * $costPrice) * $taxPercent,
    ]);

    $billItem2 = TestServices::createBillItem($bill, $this->product, 3, 50, 125, 5);

    $tax = Tax::where('name', '5% Sales')->first();

    $billItem2->taxes()->attach([
        'tax_id' => $tax->id,
        'tax_amount' => (3 * 50) * 5,
    ]);

    // Refresh the bill to ensure the total_amount is updated
    $bill->refresh();

    // Calculate the expected total amount
    $expectedTotalAmount = $billItem->total_cost + $billItem2->total_cost;

    // Assert that the bill's total_amount is correct
    $this->assertEquals($expectedTotalAmount, $bill->total_amount);
});

it('attaches the correct journal entries when a bill is paid without tax', function () {
    /**
     * The journal entries for a bill are recorded in two stages:
     *
     * Stage 1: When the bill is received
     * | Date       | Account              | Debit  | Credit |
     * |------------|----------------------|--------|--------|
     * | YYYY-MM-DD | Expense Account      | 200    |        |
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
    $quantity = 2;
    $costPrice = 100;
    $taxPercent = 0;

    $bill = TestServices::createBill($this->supplier, $quantity, $costPrice, $taxPercent);

    $billItem = TestServices::createBillItem($bill, $this->product, $quantity, $costPrice, $taxPercent);

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

    $currency_id = Currency::where('code', 'USD')->first()->id;

    // Stage 2: Pay the bill
    $payment = TestServices::createPayment($bill, 200, 'Cash', 'Expense', $currency_id, 1, 200);

    // Assert that the transaction has two journal entries
    expect($payment->journalEntries()->count())->toBe(2);

    $journalEntriesCount = JournalEntry::count();
    // at this level there should be 4 records of journal entries
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

    // Assert that the bill's untaxed_amount is correct
    expect($bill->untaxed_amount)->toBe(200.0);
});

it('updates inventory and journal entries when a bill item quantity is updated', function () {
    $quantity = 2;
    $costPrice = 100;
    $taxPercent = 0;

    $bill = TestServices::createBill($this->supplier, $quantity, $costPrice, $taxPercent);

    $billItem = TestServices::createBillItem($bill, $this->product, $quantity, $costPrice, $taxPercent);

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

    // Ensure total debits equal total credits
    $totalDebits = $bill->journalEntries()->sum('debit');
    $totalCredits = $bill->journalEntries()->sum('credit');
    expect($totalDebits)->toBe($totalCredits);

    // Update the bill item quantity
    $billItem->quantity = 4;
    $billItem->total_cost = 400;
    $billItem->untaxed_amount = 400;
    $billItem->save();

    expect($billItem->total_cost)->toBe(400.0);
    expect($billItem->untaxed_amount)->toBe(400.0);

    $bill->refresh();

    // Assert that the journal entries are updated (You'll need to add assertions for specific journal entry values)
    expect($bill->journalEntries()->count())->toBe(2);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsPayableEntry = $bill->journalEntries()->where('credit', 400 * 100)->first(); // * 100 CastMoney
    $expenseEntry = $bill->journalEntries()->where('debit', 400 * 100)->first();

    /**
     * The journal entries for a bill are recorded as follows:
     *
     * | Date       | Account              | Debit  | Credit |
     * |------------|----------------------|--------|--------|
     * | YYYY-MM-DD | Accounts Payable     |        | 400    |
     * | YYYY-MM-DD | Expense Account      | 400    |        |
     *
     * Explanation:
     * - Accounts Payable: Credited with the total bill amount (220), indicating a liability.
     * - Expense Account: Debited with the untaxed amount (200), representing the expense incurred.
     * - Tax Payable Account: Debited with the tax amount (20), representing the tax liability.
     */

    // Assert Accounts Payable entry
    expect($accountsPayableEntry->account_id)->toBe(Account::where('type', Account::TYPE_LIABILITY)->first()->id);
    expect($accountsPayableEntry->credit)->toBe(400.0);

    // Assert Expense entry
    expect($expenseEntry->account_id)->toBe(Account::where('type', Account::TYPE_EXPENSE)->first()->id);
    expect($expenseEntry->debit)->toBe(400.0);

    // Ensure total debits equal total credits
    $totalDebits = $bill->journalEntries()->sum('debit');
    $totalCredits = $bill->journalEntries()->sum('credit');
    expect($totalDebits)->toBe($totalCredits);

    // Assert that the bill's untaxed_amount is correct
    expect($bill->untaxed_amount)->toBe(400.0);
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
    $quantity = 2;
    $costPrice = 100;
    $taxPercent = 15;

    $bill = TestServices::createBill($this->supplier, $quantity, $costPrice, $taxPercent);

    $billItem = TestServices::createBillItem($bill, $this->product, $quantity, $costPrice, $taxPercent);

    $tax = Tax::where('name', '15% Sales')->first();

    $billItem->taxes()->attach([
        'tax_id' => $tax->id,
        'tax_amount' => ($quantity * $costPrice) * $taxPercent,
    ]);

    // Assert that exactly three journal entries are created and attached to the bill
    expect($bill->journalEntries()->count())->toBe(3);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsPayableEntry = $bill->journalEntries()->where('credit', 230 * 100)->first(); // * 100 CastMoney
    $expenseEntry = $bill->journalEntries()->where('debit', 200 * 100)->first();
    $taxPayableEntry = $bill->journalEntries()->where('debit', 30 * 100)->first();

    // Assert Accounts Payable entry
    expect($accountsPayableEntry->account_id)->toBe(Account::where('type', Account::TYPE_LIABILITY)->first()->id);
    expect($accountsPayableEntry->credit)->toBe(230.0);

    // Assert Expense entry
    expect($expenseEntry->account_id)->toBe(Account::where('type', Account::TYPE_EXPENSE)->first()->id);
    expect($expenseEntry->debit)->toBe(200.0);

    // Assert Tax Payable entry
    expect($taxPayableEntry->account_id)->toBe(Account::where('name', 'Tax Payable')->first()->id);
    expect($taxPayableEntry->debit)->toBe(30.0);

    // stage 2: Pay the bill
    $currency_id = Currency::where('code', 'USD')->first()->id;

    // Stage 2: Pay the bill
    $payment = TestServices::createPayment($bill, 230, 'Cash', 'Expense', $currency_id, 1, 230);
    expect($payment->amount)->toBe(230.0);

    // Assert that the transaction has two journal entries
    expect($payment->journalEntries()->count())->toBe(2);

    $journalEntriesCount = JournalEntry::count();
    // at this level there should be 4 records of journal entries
    expect($journalEntriesCount)->toBe(5);

    $journalEntriesCount = JournalEntry::count();

    // at this level there should be 5 records of journal entries
    expect($journalEntriesCount)->toBe(5);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsPayableEntry = $payment->journalEntries()
        ->where('account_id', Account::where('name', 'Accounts Payable')->first()->id)
        ->where('debit', 230 * 100)
        ->first();

    $cashEntry = $payment->journalEntries()
        ->where('account_id', Account::where('name', 'Cash')->first()->id)
        ->where('credit', 230 * 100)
        ->first();

    expect($accountsPayableEntry)->not->toBeNull();
    expect($cashEntry)->not->toBeNull();

    $bill->refresh();

    expect($bill->status)->toBe('Paid');

    // Ensure total debits equal total credits
    $totalDebits = $bill->journalEntries()->sum('debit');
    $totalCredits = $bill->journalEntries()->sum('credit');
    expect($totalDebits)->toBe($totalCredits);

    // Assert that the bill's untaxed_amount is correct
    expect($bill->untaxed_amount)->toBe(200.0);
});

it('attaches the correct journal entries when a bill is partially paid without tax', function () {
    $quantity = 2;
    $costPrice = 100;
    $taxPercent = 0;

    $bill = TestServices::createBill($this->supplier, $quantity, $costPrice, $taxPercent);

    $billItem = TestServices::createBillItem($bill, $this->product, $quantity, $costPrice, $taxPercent);

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

    $currency_id = Currency::where('code', 'USD')->first()->id;

    // Stage 2: Pay the bill
    $payment = TestServices::createPayment($bill, 100, 'Cash', 'Expense', $currency_id, 1, 200);

    expect($payment->amount)->toBe(100.0);

    // Assert that the transaction has two journal entries
    expect($payment->journalEntries()->count())->toBe(2);

    $journalEntriesCount = JournalEntry::count();
    // at this level there should be 4 records of journal entries
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

    // Assert that the bill's untaxed_amount is correct
    expect($bill->untaxed_amount)->toBe(200.0);
});

it('attaches the correct journal entries when a bill is partially paid with tax', function () {
    $quantity = 2;
    $costPrice = 100;
    $taxPercent = 15;

    $bill = TestServices::createBill($this->supplier, $quantity, $costPrice, $taxPercent);

    $billItem = TestServices::createBillItem($bill, $this->product, $quantity, $costPrice, $taxPercent);

    $tax = Tax::where('name', '15% Sales')->first();

    $billItem->taxes()->attach([
        'tax_id' => $tax->id,
        'tax_amount' => ($quantity * $costPrice) * $taxPercent,
    ]);

    // Assert that exactly three journal entries are created and attached to the bill
    expect($bill->journalEntries()->count())->toBe(3);

    // Assert that the journal entries have the correct amounts and accounts
    $accountsPayableEntry = $bill->journalEntries()->where('credit', 230 * 100)->first(); // * 100 CastMoney
    $expenseEntry = $bill->journalEntries()->where('debit', 200 * 100)->first();
    $taxPayableEntry = $bill->journalEntries()->where('debit', 30 * 100)->first();

    // Assert Accounts Payable entry
    expect($accountsPayableEntry->account_id)->toBe(Account::where('type', Account::TYPE_LIABILITY)->first()->id);
    expect($accountsPayableEntry->credit)->toBe(230.0);

    // Assert Expense entry
    expect($expenseEntry->account_id)->toBe(Account::where('type', Account::TYPE_EXPENSE)->first()->id);
    expect($expenseEntry->debit)->toBe(200.0);

    // Assert Tax Payable entry
    expect($taxPayableEntry->account_id)->toBe(Account::where('name', 'Tax Payable')->first()->id);
    expect($taxPayableEntry->debit)->toBe(30.0);

    // Partial payment
    $currency_id = Currency::where('code', 'USD')->first()->id;

    // Stage 2: Pay the bill
    $payment = TestServices::createPayment($bill, 110, 'Cash', 'Expense', $currency_id, 1, 230);

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

    // Assert that the bill's untaxed_amount is correct
    expect($bill->untaxed_amount)->toBe(200.0);
});

it('calculates bill amounts correctly', function () {
    $quantity = 2;
    $costPrice = 100;
    $taxPercent = 15;

    $bill = TestServices::createBill($this->supplier, $quantity, $costPrice, $taxPercent);
    $billItem = TestServices::createBillItem($bill, $this->product, $quantity, $costPrice, $taxPercent);

    // Assert the amounts are calculated correctly
    expect($bill->untaxed_amount)->toBe(200.0);
    expect($bill->tax_amount)->toBe(30.0);
    expect($bill->total_amount)->toBe(230.0);

    // Verify that total_amount equals untaxed_amount plus tax_amount
    expect($bill->total_amount)->toBe($bill->untaxed_amount + $bill->tax_amount);
});

it('calculates bill amounts correctly when bill item is updated', function () {
    $quantity = 2;
    $costPrice = 100;
    $taxPercent = 15;

    $bill = TestServices::createBill($this->supplier, $quantity, $costPrice, $taxPercent);
    $billItem = TestServices::createBillItem($bill, $this->product, $quantity, $costPrice, $taxPercent);

    // Initial assertions
    expect($bill->untaxed_amount)->toBe(200.0);
    expect($bill->tax_amount)->toBe(30.0);
    expect($bill->total_amount)->toBe(230.0);

    // Update bill item
    $billItem->update([
        'quantity' => 3,
        'total_cost' => 345.0,
        'tax_amount' => 45.0,
        'untaxed_amount' => 300.0,
    ]);

    // Refresh the bill to get updated values
    $bill->refresh();

    // Assert the amounts are updated correctly
    expect($bill->untaxed_amount)->toBe(300.0);
    expect($bill->tax_amount)->toBe(45.0);
    expect($bill->total_amount)->toBe(345.0);

    // Verify that total_amount equals untaxed_amount plus tax_amount
    expect($bill->total_amount)->toBe($bill->untaxed_amount + $bill->tax_amount);
});

it('calculates amount_due correctly when bill is partially paid', function () {
    $quantity = 2;
    $costPrice = 100;
    $taxPercent = 15;

    $bill = TestServices::createBill($this->supplier, $quantity, $costPrice, $taxPercent);
    $billItem = TestServices::createBillItem($bill, $this->product, $quantity, $costPrice, $taxPercent);

    // Initial amount due should equal total amount
    expect($bill->amount_due)->toBe(230.0);
    expect($bill->total_amount)->toBe(230.0);
    expect($bill->status)->toBe('Draft');

    // Make first partial payment
    $currency_id = Currency::where('code', 'USD')->first()->id;
    $payment1 = TestServices::createPayment($bill, 100, 'Cash', 'Expense', $currency_id, 1, 230);

    $bill->refresh();

    // Check amount due after first payment
    expect($bill->amount_due)->toBe(130.0);
    expect($bill->status)->toBe('Partial');

    // Make second partial payment
    $payment2 = TestServices::createPayment($bill, 80, 'Cash', 'Expense', $currency_id, 1, 230);

    $bill->refresh();

    // Check amount due after second payment
    expect($bill->amount_due)->toBe(50.0);
    expect($bill->status)->toBe('Partial');

    // Make final payment
    $payment3 = TestServices::createPayment($bill, 50, 'Cash', 'Expense', $currency_id, 1, 230);

    $bill->refresh();

    // Check amount due after final payment
    expect($bill->amount_due)->toBe(0.0);
    expect($bill->status)->toBe('Paid');

    // Verify total payments equal total amount
    $totalPayments = $bill->payments()->sum('amount');
    expect($totalPayments)->toBe(230 * 100);
});

it('calculates bill amounts correctly with multiple items and mixed taxes', function () {
    $quantity1 = 2;
    $costPrice1 = 100;
    $taxPercent1 = 15;

    $quantity2 = 3;
    $costPrice2 = 50;
    $taxPercent2 = 5;

    $quantity3 = 1;
    $costPrice3 = 200;
    $taxPercent3 = 0;

    $bill = TestServices::createBill($this->supplier, $quantity1, $costPrice1, $taxPercent1);

    $billItem1 = TestServices::createBillItem($bill, $this->product, $quantity1, $costPrice1, $taxPercent1);
    $billItem2 = TestServices::createBillItem($bill, $this->product, $quantity2, $costPrice2, $taxPercent2);
    $billItem3 = TestServices::createBillItem($bill, $this->product, $quantity3, $costPrice3, $taxPercent3);

    // Refresh the bill to ensure the total_amount is updated
    $bill->refresh();

    // Calculate the expected amounts
    $expectedUntaxedAmount = $billItem1->untaxed_amount + $billItem2->untaxed_amount + $billItem3->untaxed_amount;
    $expectedTaxAmount = $billItem1->tax_amount + $billItem2->tax_amount;
    $expectedTotalAmount = $expectedUntaxedAmount + $expectedTaxAmount;

    // Assert that the bill's amounts are correct
    expect($bill->untaxed_amount)->toBe($expectedUntaxedAmount);
    expect($bill->tax_amount)->toBe($expectedTaxAmount);
    expect($bill->total_amount)->toBe($expectedTotalAmount);
});

it('calculates bill amounts correctly with five items and mixed taxes', function () {
    $quantity1 = 2;
    $costPrice1 = 100;
    $taxPercent1 = 15;

    $quantity2 = 3;
    $costPrice2 = 50;
    $taxPercent2 = 5;

    $quantity3 = 1;
    $costPrice3 = 200;
    $taxPercent3 = 0;

    $quantity4 = 4;
    $costPrice4 = 75;
    $taxPercent4 = 10;

    $quantity5 = 5;
    $costPrice5 = 30;
    $taxPercent5 = 8;

    $bill = TestServices::createBill($this->supplier, $quantity1, $costPrice1, $taxPercent1);

    $billItem1 = TestServices::createBillItem($bill, $this->product, $quantity1, $costPrice1, $taxPercent1);
    $billItem2 = TestServices::createBillItem($bill, $this->product, $quantity2, $costPrice2, $taxPercent2);
    $billItem3 = TestServices::createBillItem($bill, $this->product, $quantity3, $costPrice3, $taxPercent3);
    $billItem4 = TestServices::createBillItem($bill, $this->product, $quantity4, $costPrice4, $taxPercent4);
    $billItem5 = TestServices::createBillItem($bill, $this->product, $quantity5, $costPrice5, $taxPercent5);

    // Refresh the bill to ensure the total_amount is updated
    $bill->refresh();

    // Calculate the expected amounts
    $expectedUntaxedAmount = $billItem1->untaxed_amount + $billItem2->untaxed_amount + $billItem3->untaxed_amount + $billItem4->untaxed_amount + $billItem5->untaxed_amount;
    $expectedTaxAmount = $billItem1->tax_amount + $billItem2->tax_amount + $billItem4->tax_amount + $billItem5->tax_amount;
    $expectedTotalAmount = $expectedUntaxedAmount + $expectedTaxAmount;

    // Assert that the bill's amounts are correct
    expect($bill->untaxed_amount)->toBe($expectedUntaxedAmount);
    expect($bill->tax_amount)->toBe($expectedTaxAmount);
    expect($bill->total_amount)->toBe($expectedTotalAmount);
});

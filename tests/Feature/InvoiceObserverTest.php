<?php

namespace Xoshbin\JmeryarAccounting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\Customer;
use Xoshbin\JmeryarAccounting\Models\Invoice;
use Xoshbin\JmeryarAccounting\Models\InvoiceItem;
use Xoshbin\JmeryarAccounting\Models\Product;
use Xoshbin\JmeryarAccounting\Tests\TestCase;

class InvoiceObserverTest extends TestCase
{
    use RefreshDatabase;
    public function test_attaches_journal_entries_to_the_invoice()
    {
        // Create an invoice
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);

        // Create invoice items
        $product = Product::factory()->create();
        $invoiceItem = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100,
            'tax_amount' => 0,
        ]);

        // Assert that journal entries are created and attached to the invoice
        $this->assertEquals(3, $invoice->journalEntries()->count());
    }

}
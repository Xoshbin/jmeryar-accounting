<?php

namespace Tests\Services;

use Illuminate\Database\Eloquent\Model;
use Xoshbin\JmeryarAccounting\Models\Bill;
use Xoshbin\JmeryarAccounting\Models\BillItem;
use Xoshbin\JmeryarAccounting\Models\Customer;
use Xoshbin\JmeryarAccounting\Models\Invoice;
use Xoshbin\JmeryarAccounting\Models\InvoiceItem;
use Xoshbin\JmeryarAccounting\Models\Payment;
use Xoshbin\JmeryarAccounting\Models\Product;
use Xoshbin\JmeryarAccounting\Models\Supplier;

class TestServices
{
    public static function createBill(Supplier $supplier, int $quantity, int $costPrice, int $taxPercent = 0): Bill
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

    public static function createBillItem(Bill $bill, Product $product, int $quantity, int $costPrice, int $taxPercent = 0): BillItem
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

    public static function createPayment(Model $parent, int $amount, string $paymentMethod, string $paymentType, int $currencyId, int $exchangeRate, int $amountInInvoiceCurrency): Payment
    {
        $payment = $parent->payments()->create([
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

    public static function createInvoice(Customer $customer, int $quantity, int $unit_price, int $taxPercent = 0): Invoice
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

    public static function createInvoiceItem(Invoice $invoice, Product $product, int $quantity, int $unitPrice, int $taxPercent = 0): InvoiceItem
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
}

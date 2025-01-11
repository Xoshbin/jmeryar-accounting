<?php

namespace Xoshbin\JmeryarAccounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Xoshbin\JmeryarAccounting\Models\Currency;
use Xoshbin\JmeryarAccounting\Models\Invoice;
use Xoshbin\JmeryarAccounting\Models\InvoiceItem;
use Xoshbin\JmeryarAccounting\Models\Payment;
use Xoshbin\JmeryarAccounting\Models\Tax;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Invoice::factory(5)->create()->each(function ($invoice) {
            $invoiceItems = InvoiceItem::factory(rand(1, 5))->create([
                'invoice_id' => $invoice->id,
            ])->each(function ($invoiceItem) {
                $tax = Tax::where('tax_computation', '!=', 'Fixed')->inRandomOrder()->first();

                // Update the invoice item with the calculated tax values
                $invoiceItem->update([
                    'untaxed_amount' => $invoiceItem->total_price - ($invoiceItem->unit_price * ($tax->amount / 100)),
                    'tax_amount' => $invoiceItem->unit_price * ($tax->amount / 100),
                ]);
            });

            $totalAmount = $invoiceItems->sum('total_price');
            $totalUntaxedAmount = $invoiceItems->sum('untaxed_amount');
            $totalTaxAmount = $invoiceItems->sum('tax_amount');

            $invoice->update([
                'total_amount' => $totalAmount,
                'untaxed_amount' => $totalUntaxedAmount,
                'tax_amount' => $totalTaxAmount,
                'amount_due' => $totalAmount, // Initialize amount due
                'total_paid_amount' => 0,    // Initialize total paid
                'status' => 'Draft',        // Initial status
            ]);

            $remainingBalance = $totalAmount;
            $paymentCount = rand(1, 3); // Random number of payments

            $currency = Currency::where('code', 'USD')->first();
            $exchangeRate = $currency->exchangeRatesAsBase->first()->rate;

            for ($i = 0; $i < $paymentCount; $i++) {
                // Calculate the payment amount
                $paymentAmount = mt_rand(10, 100) / 1000 * $remainingBalance;
                $paymentAmount = min($paymentAmount, $remainingBalance);

                // Convert the payment amount to the invoice currency
                $amountInInvoiceCurrency = round($paymentAmount / $exchangeRate, 2);

                // Create the payment instance
                $payment = new Payment([
                    'amount' => $paymentAmount,
                    'payment_date' => now(),
                    'payment_type' => Payment::TYPE_INCOME,
                    'payment_method' => 'Cash',
                    'currency_id' => $currency->id,
                    'exchange_rate' => $exchangeRate,
                    'amount_in_document_currency' => $amountInInvoiceCurrency,
                    'note' => 'Generated payment',
                ]);

                // Attach the payment to the invoice
                $invoice->payments()->save($payment);
            }
        });
    }
}

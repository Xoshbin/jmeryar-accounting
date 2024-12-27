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
            ]);

            $remainingBalance = $totalAmount;
            $paymentCount = rand(1, 3);

            $totalPaidAmountInInvoiceCurrency = $invoice->total_paid_amount ?? 0; // Total paid in invoice currency

            for ($i = 0; $i < $paymentCount; $i++) {
                // Calculate the payment amount, ensuring it doesn't exceed the remaining balance
                $paymentAmount = mt_rand(10, 100) / 100 * $remainingBalance;
                $paymentAmount = min($paymentAmount, $remainingBalance);

                $currency = Currency::where('code', 'USD')->first();

                // Retrieve exchange rate for the invoice's base currency
                $exchangeRate = $currency->exchangeRatesAsBase->first()->rate;

                // Calculate the amount in the invoice's currency using the exchange rate
                $amountInInvoiceCurrency = $paymentAmount / $exchangeRate;

                // Create the payment instance without saving to the database yet
                $payment = new Payment([
                    'amount' => $paymentAmount,
                    'payment_date' => now(),
                    'payment_type' => Payment::TYPE_INCOME,
                    'payment_method' => 'Cash',
                    'currency_id' => $currency->id,
                    'exchange_rate' => $exchangeRate,
                    'amount_in_invoice_currency' => $amountInInvoiceCurrency, // Payment in invoice currency
                    'note' => 'Generated payment',
                ]);

                // Attach the payment
                $invoice->payments()->save($payment);

                // Update the total paid amount in the invoice's currency
                $totalPaidAmountInInvoiceCurrency += $amountInInvoiceCurrency;

                // Update the remaining balance in the invoice's currency
                $remainingBalance = $totalAmount - $totalPaidAmountInInvoiceCurrency;

                $invoice->update([
                    'amount_due' => $remainingBalance,
                    'total_paid_amount' => $totalPaidAmountInInvoiceCurrency,
                ]);

                if ($remainingBalance <= 0) {
                    break;
                }
            }
        });
    }
}

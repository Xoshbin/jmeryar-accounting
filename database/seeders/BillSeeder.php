<?php

namespace Xoshbin\JmeryarAccounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Xoshbin\JmeryarAccounting\Models\Bill;
use Xoshbin\JmeryarAccounting\Models\BillItem;
use Xoshbin\JmeryarAccounting\Models\Currency;
use Xoshbin\JmeryarAccounting\Models\Payment;
use Xoshbin\JmeryarAccounting\Models\Tax;

class BillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Bill::factory(5)->create()->each(function ($bill) {
            // Create Bill Items
            $billItems = BillItem::factory(rand(1, 5))->create([
                'bill_id' => $bill->id,
            ])->each(function ($billItem) {
                $tax = Tax::where('tax_computation', '!=', 'Fixed')->inRandomOrder()->first();

                // Update the invoice item with the calculated tax values
                $billItem->update([
                    'untaxed_amount' => $billItem->total_cost - ($billItem->cost_price * ($tax->amount / 100)),
                    'tax_amount' => $billItem->cost_price * ($tax->amount / 100),
                ]);
            });

            // Calculate and set total amount for the bill
            $totalAmount = $billItems->sum('total_cost');
            $totalUntaxedAmount = $billItems->sum('untaxed_amount');
            $totalTaxAmount = $billItems->sum('tax_amount');
            $bill->update([
                'total_amount' => $totalAmount,
                'untaxed_amount' => $totalUntaxedAmount,
                'tax_amount' => $totalTaxAmount,
            ]);

            $remainingBalance = $totalAmount;
            $paymentCount = rand(1, 3);

            $totalPaidAmountInInvoiceCurrency = $invoice->total_paid_amount ?? 0; // Total paid in bill currency

            for ($i = 0; $i < $paymentCount; $i++) {
                // Calculate the payment amount, ensuring it doesn't exceed the remaining balance
                $paymentAmount = mt_rand(10, 100) / 100 * $remainingBalance;
                $paymentAmount = min($paymentAmount, $remainingBalance);

                $currency = Currency::where('code', 'USD')->first();

                // Retrieve exchange rate for the invoice's base currency
                $exchangeRate = $currency->exchangeRatesAsBase->first()->rate;

                // Calculate the amount in the invoice's currency using the exchange rate
                $amountInInvoiceCurrency = $paymentAmount / $exchangeRate;

                $payment = new Payment([
                    'amount' => $paymentAmount,
                    'payment_date' => now(),
                    'payment_type' => Payment::TYPE_EXPENSE,
                    'payment_method' => 'Cash',
                    'note' => 'Generated payment',
                    'currency_id' => $currency->id,
                    'exchange_rate' => $exchangeRate,
                    'amount_in_invoice_currency' => $amountInInvoiceCurrency,
                ]);

                // Attach the payment
                $bill->payments()->save($payment);

                // Update the total paid amount in the bill's currency
                $totalPaidAmountInInvoiceCurrency += $amountInInvoiceCurrency;

                // Update the total_paid_amount by adding the current payment amount
                $remainingBalance = $totalAmount - $totalPaidAmountInInvoiceCurrency;

                $bill->update([
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

<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\InvoiceResource\Pages;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\InvoiceResource;
use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\Invoice;
use Xoshbin\JmeryarAccounting\Models\JournalEntry;
use Xoshbin\JmeryarAccounting\Models\Payment;
use Xoshbin\JmeryarAccounting\Models\Setting;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    public function mutateFormDataBeforeSave(array $data): array
    {
        $data['revenue_account_id'] = Account::where('type', Account::TYPE_REVENUE)->first()->id;
        $data['asset_account_id'] = Account::where('name', Account::TYPE_ACCOUNTS_RECEIVABLE)->first()->id;

        return $data;
    }

    /**
     * @deprecated This method is deprecated, kept it just for reference
     * Use PaymentObserver Instead
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),

            Actions\Action::make('pdf')
                ->label(__('jmeryar-accounting::invoices.form.PDF/Print'))
                ->color('success')
                ->action(function (Model $record) {
                    Pdf::setOptions(['debugCss' => false]);
                    $setting = Setting::first();

                    return response()->streamDownload(function () use ($record, $setting) {
                        echo Pdf::loadHtml(
                            Blade::render('jmeryar-accounting::invoices.invoice', ['record' => $record, 'setting' => $setting]),
                        )->stream();
                    }, $record->invoice_number . '.pdf');
                }),

            Actions\Action::make('Register payment')
                ->label(__('jmeryar-accounting::invoices.form.Register payment'))
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label(__('jmeryar-accounting::invoices.form.amount'))
                        ->default($this->record->total_amount - $this->record->total_paid_amount)
                        ->required()
                        ->numeric(),
                    Forms\Components\DatePicker::make('payment_date')
                        ->label(__('jmeryar-accounting::invoices.form.payment_date'))
                        ->default(now())
                        ->nullable(),
                    Forms\Components\Select::make('payment_method')
                        ->label(__('jmeryar-accounting::invoices.form.payment_method'))
                        ->default('cash')
                        ->options([
                            'Cash' => 'Cash',
                            'Bank' => 'Bank',
                        ]),
                    Forms\Components\Textarea::make('note')
                        ->label(__('jmeryar-accounting::invoices.form.note'))
                        ->default('')
                        ->required()
                        ->columnSpanFull(),
                ])->action(function (array $data, Invoice $record): void {

                    $payment = Payment::create([
                        'amount' => $data['amount'],
                        'payment_date' => $data['payment_date'],
                        'payment_type' => 'income',
                        'payment_method' => $data['payment_method'],
                        'note' => $data['note'],
                    ]);

                    // Attach the payment to the invoice
                    $record->payments()->attach($payment->id);

                    // Create the associated transaction
                    $transaction = $payment->transactions()->create([
                        'date' => now(),
                        'note' => 'Transaction for payment ID ' . $record->id,
                        'amount' => $data['amount'],
                        'transaction_type' => 'credit', // For an invoice, this should be a credit transaction.
                    ]);

                    // Journal entry for payment account (Debit)
                    $transaction->journalEntries()->create([
                        'account_id' => Account::where('name', 'Cash')->first()->id, // cash or bank account ID
                        'amount' => $data['amount'],
                        'entry_type' => JournalEntry::TYPE_DEBIT, // Debit the payment account (Cash/Bank)
                    ]);

                    // Journal entry for Accounts Receivable (Credit)
                    $transaction->journalEntries()->create([
                        'account_id' => Account::where('name', 'Accounts Receivable')->first()->id, // AR account ID
                        'amount' => $data['amount'],
                        'entry_type' => JournalEntry::TYPE_CREDIT, // Credit Accounts Receivable
                    ]);

                    // Update the status of the invoice based on the payment
                    if ($data['amount'] < $this->record->total_amount) {
                        $this->record->update(['status' => 'partial']);
                    } else {
                        $this->record->update(['status' => 'paid']);
                    }
                }),
        ];
    }
}

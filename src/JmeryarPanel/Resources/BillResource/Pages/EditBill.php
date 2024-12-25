<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\BillResource\Pages;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\BillResource;
use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\Bill;
use Xoshbin\JmeryarAccounting\Models\JournalEntry;
use Xoshbin\JmeryarAccounting\Models\Payment;

class EditBill extends EditRecord
{
    protected static string $resource = BillResource::class;

    public function mutateFormDataBeforeSave(array $data): array
    {
        $data['expense_account_id'] = Account::where('type', Account::TYPE_EXPENSE)->first()->id;
        $data['liability_account_id'] = Account::where('type', Account::TYPE_LIABILITY)->first()->id;

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
            Actions\Action::make('Register payment')
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->default($this->record->total_amount - $this->record->total_paid_amount)
                        ->required()
                        ->numeric(),
                    Forms\Components\DatePicker::make('payment_date')
                        ->default(now())
                        ->nullable(),
                    Forms\Components\Select::make('payment_method')
                        ->default('cash')
                        ->options([
                            'Cash' => 'Cash',
                            'Bank' => 'Bank',
                        ]),
                    Forms\Components\Textarea::make('note')
                        ->default('')
                        ->required()
                        ->columnSpanFull(),
                ])->action(function (array $data, Bill $record): void {

                    // Create the payment record
                    $payment = Payment::create([
                        'amount' => $data['amount'],
                        'payment_date' => $data['payment_date'],
                        'payment_type' => 'expense',
                        'payment_method' => $data['payment_method'],
                        'note' => $data['note'],
                    ]);

                    // Attach the payment to the bill
                    $record->payments()->attach($payment->id);

                    // Create the associated transaction
                    $transaction = $payment->transactions()->create([
                        'date' => now(),
                        'note' => 'Transaction for payment ID '.$record->id,
                        'amount' => $data['amount'],
                        'transaction_type' => 'debit', // For a bill, this should be a debit transaction.
                    ]);

                    // Journal entry for payment account (Debit)
                    $transaction->journalEntries()->create([
                        'account_id' => Account::where('name', $data['payment_method'] === 'cash' ? 'Cash' : 'Bank')->first()->id,
                        'amount' => $data['amount'],
                        'entry_type' => JournalEntry::TYPE_DEBIT, // Correct: Debit the payment account (Cash/Bank)
                    ]);

                    // Journal entry for Accounts Payable (Credit)
                    $transaction->journalEntries()->create([
                        'account_id' => Account::where('name', 'Accounts Payable')->first()->id,
                        'amount' => $data['amount'],
                        'entry_type' => JournalEntry::TYPE_CREDIT, // Credit Accounts Payable
                    ]);

                    // Update the status of the bill based on the payment
                    if ($data['amount'] < $this->record->total_amount) {
                        $this->record->update(['status' => 'partial']);
                    } else {
                        $this->record->update(['status' => 'paid']);
                    }
                }),
        ];
    }
}

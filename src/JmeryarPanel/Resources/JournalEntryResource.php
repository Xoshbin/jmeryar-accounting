<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Tables\Columns\MoneyColumn;
use Xoshbin\JmeryarAccounting\Models\Currency;
use Xoshbin\JmeryarAccounting\Models\JournalEntry;

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    //    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Accounting';

    public static function getNavigationLabel(): string
    {
        return __('jmeryar-accounting::journal_entries.title');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('jmeryar-accounting::journal_entries.table.created_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('journal_entry')
                    ->label(__('jmeryar-accounting::journal_entries.table.journal_entry'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('account.name')
                    ->label(__('jmeryar-accounting::journal_entries.table.account_name'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('partner')
                    ->label(__('jmeryar-accounting::journal_entries.table.partner'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('jmeryar-accounting::journal_entries.table.description'))
                    ->numeric()
                    ->sortable(),
                MoneyColumn::make('debit')
                    ->currencyCode(function ($record) {
                        $parent = $record->bills->first() // Check if related to a Bill
                            ?? $record->invoices->first() // If not, check Invoice
                            ?? $record->payments->first(); // If not, check Payment

                        return $parent?->currency?->code;
                    })
                    ->label(__('jmeryar-accounting::journal_entries.table.debit'))
                    ->sortable(),
                MoneyColumn::make('credit')
                    ->label(__('jmeryar-accounting::journal_entries.table.credit'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('jmeryar-accounting::journal_entries.table.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                //                Tables\Actions\EditAction::make(),
                Action::make('item')
                    ->label(function (JournalEntry $record) {
                        // Attempt to get a Bill or Invoice associated with the JournalEntry
                        $bill = $record->bills->first();
                        $invoice = $record->invoices->first();

                        if ($bill) {
                            return $bill->bill_number;
                        }

                        if ($invoice) {
                            return $invoice->invoice_number;
                        }

                        return '#';
                    })
                    ->url(function (JournalEntry $record): string {
                        // Attempt to get a Bill or Invoice associated with the JournalEntry
                        $bill = $record->bills->first();
                        $invoice = $record->invoices->first();

                        if ($bill) {
                            return BillResource::getUrl('edit', ['record' => $bill]);
                        }

                        if ($invoice) {
                            return InvoiceResource::getUrl('edit', ['record' => $invoice]);
                        }

                        return '#'; // Fallback URL or handle cases with no associated records
                    }),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\JournalEntryResource\Pages\ListJournalEntries::route('/'),
            'create' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\JournalEntryResource\Pages\CreateJournalEntry::route('/create'),
            'edit' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\JournalEntryResource\Pages\EditJournalEntry::route('/{record}/edit'),
        ];
    }
}

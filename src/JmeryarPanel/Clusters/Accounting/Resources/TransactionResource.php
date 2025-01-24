<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Tables\Columns\MoneyColumn;
use Xoshbin\JmeryarAccounting\Models\Transaction;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Accounting';

    protected static ?string $cluster = Accounting::class;

    public static function getPluralLabel(): string
    {
        return __('jmeryar-accounting::transactions.title');
    }

    public static function getLabel(): string
    {
        return __('jmeryar-accounting::transactions.singular');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->label(__('jmeryar-accounting::transactions.form.date'))
                    ->required(),
                Forms\Components\Textarea::make('note')
                    ->label(__('jmeryar-accounting::transactions.form.note'))
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('amount')
                    ->label(__('jmeryar-accounting::transactions.form.amount'))
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('transaction_type')
                    ->label(__('jmeryar-accounting::transactions.form.transaction_type'))
                    ->options([
                        'Debit' => 'Debit',
                        'Credit' => 'Credit',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label(__('jmeryar-accounting::transactions.table.date'))
                    ->dateTime()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('note')
                    ->label(__('jmeryar-accounting::transactions.table.note'))
                    ->searchable(),
                MoneyColumn::make('amount')
                    ->label(__('jmeryar-accounting::transactions.table.amount'))
                    ->currencyCode(fn ($record) => $record->payments->first()?->currency?->code ?? 'USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_type')
                    ->label(__('jmeryar-accounting::transactions.table.transaction_type')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('jmeryar-accounting::transactions.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('jmeryar-accounting::transactions.table.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                //                Tables\Actions\EditAction::make(),
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
            'index' => Accounting\Resources\TransactionResource\Pages\ListTransactions::route('/'),
            // 'create' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\TransactionResource\Pages\CreateTransaction::route('/create'),
            // 'edit' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\TransactionResource\Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}

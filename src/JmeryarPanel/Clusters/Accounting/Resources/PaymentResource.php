<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Forms\Components\Field\MoneyInput;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Tables\Columns\MoneyColumn;
use Xoshbin\JmeryarAccounting\Models\Currency;
use Xoshbin\JmeryarAccounting\Models\Payment;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Accounting';

    protected static ?string $cluster = Accounting::class;

    public static function getPluralLabel(): string
    {
        return __('jmeryar-accounting::payments.title');
    }

    public static function getLabel(): string
    {
        return __('jmeryar-accounting::payments.singular');
    }

    public static function form(Form $form): Form
    {
        return
            $form
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Section::make()
                                ->columns(2)
                                ->schema([
                                    MoneyInput::make('amount')
                                        ->label(__('jmeryar-accounting::payments.form.amount'))
                                        ->required()
                                        ->numeric(),
                                    Forms\Components\DatePicker::make('payment_date')
                                        ->label(__('jmeryar-accounting::payments.form.payment_date'))
                                        ->required(),
                                    Forms\Components\TextInput::make('payment_type')
                                        ->label(__('jmeryar-accounting::payments.form.payment_type'))
                                        ->required(),
                                    Forms\Components\TextInput::make('payment_method')
                                        ->label(__('jmeryar-accounting::payments.form.payment_method'))
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\Textarea::make('note')
                                        ->label(__('jmeryar-accounting::payments.form.note'))
                                        ->columnSpanFull(),
                                ])
                                ->columnSpan(2),
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\Section::make()->schema([
                                        //
                                    ]),
                                ])
                                ->columnSpan(1),
                        ]),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                MoneyColumn::make('amount')
                    ->label(__('jmeryar-accounting::payments.table.amount'))
                    ->currencyCode(fn ($record) => Currency::find($record->currency_id)?->code)
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label(__('jmeryar-accounting::payments.table.payment_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_type')
                    ->label(__('jmeryar-accounting::payments.table.payment_type')),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label(__('jmeryar-accounting::payments.table.payment_method'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('jmeryar-accounting::payments.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('jmeryar-accounting::payments.table.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Accounting\Resources\PaymentResource\Pages\ListPayments::route('/'),
            'create' => Accounting\Resources\PaymentResource\Pages\CreatePayment::route('/create'),
            'edit' => Accounting\Resources\PaymentResource\Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}

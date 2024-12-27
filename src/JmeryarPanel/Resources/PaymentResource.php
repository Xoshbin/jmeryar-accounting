<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Xoshbin\JmeryarAccounting\Models\Payment;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    //    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Accounting';

    public static function getNavigationLabel(): string
    {
        return __('jmeryar-accounting::payments.title');
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
                                Forms\Components\TextInput::make('amount')
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
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('jmeryar-accounting::payments.table.amount'))
                    ->numeric()
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
            'index' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\PaymentResource\Pages\ListPayments::route('/'),
            'create' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\PaymentResource\Pages\CreatePayment::route('/create'),
            'edit' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\PaymentResource\Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}

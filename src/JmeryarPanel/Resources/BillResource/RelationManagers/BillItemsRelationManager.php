<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\BillResource\RelationManagers;

use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BillItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'BillItems';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                        if ($state) {
                            $product = Product::find($state);
                            if ($product) {
                                $set('unit_price', $product->cost_price);
                            }
                        }
                    }),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->live()
                    ->afterStateUpdated(function (Set $set, $get, $state) {
                        $set('total_cost', $state * $get('unit_price'));
                    }),
                Forms\Components\TextInput::make('unit_price')
                    ->required()
                    ->numeric()
                    ->live()
                    ->afterStateUpdated(function (Set $set, $get, $state) {
                        $set('total_cost', $get('quantity') * $state);
                    }),
                Forms\Components\TextInput::make('total_cost')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('expense_account_id')
                    ->relationship('expenseAccount', 'name')
                    ->default(fn () => Account::where('type', Account::TYPE_EXPENSE)->first()->id)
                    ->extraAttributes(['class' => 'hidden'])
                    ->label('')
                    ->required(),
                Forms\Components\Select::make('liability_account_id')
                    ->relationship('liabilityAccount', 'name')
                    ->default(fn () => Account::where('type', Account::TYPE_LIABILITY)->first()->id)
                    ->extraAttributes(['class' => 'hidden'])
                    ->label('')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('quantity')
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_cost')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

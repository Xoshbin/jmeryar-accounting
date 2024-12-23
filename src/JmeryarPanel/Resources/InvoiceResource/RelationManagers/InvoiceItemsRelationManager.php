<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Xoshbin\JmeryarAccounting\Models\Account;
use Xoshbin\JmeryarAccounting\Models\Product;

class InvoiceItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'invoiceItems';

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
                                $set('quantity', $product->quantity);
                                $set('unit_price', $product->unit_price);
                                $set('total_price', $product->unit_price * $get('quantity'));
                            }
                        }
                    }),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->live()
                    ->afterStateUpdated(function (Set $set, $get, $state) {
                        $set('total_price', $state * $get('unit_price'));
                    }),
                Forms\Components\TextInput::make('unit_price')
                    ->required()
                    ->numeric()
                    ->live()
                    ->afterStateUpdated(function (Set $set, $get, $state) {
                        $set('total_price', $get('quantity') * $state);
                    }),
                Forms\Components\TextInput::make('total_price')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('revenue_account_id')
                    ->relationship('revenueAccount', 'name')
                    ->default(fn () => Account::where('type', Account::TYPE_REVENUE)->first()->id)
                    ->required(),
                Forms\Components\Select::make('inventory_account_id')
                    ->relationship('inventoryAccount', 'name')
                    ->default(fn () => Account::where('type', Account::TYPE_ASSET)->first()->id)
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
                Tables\Columns\TextColumn::make('total_price')
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

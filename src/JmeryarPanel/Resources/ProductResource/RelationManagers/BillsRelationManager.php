<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class InventoryBatchesRelationManager extends RelationManager
{
    protected static string $relationship = 'inventoryBatches';

    public function form(Form $form): Form
    {
        return
            $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Section::make()
                            ->columns(2)
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label(__('jmeryar-accounting::inventory.form.name'))
                                    ->relationship('product', 'name')
                                    ->required(),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->label(__('jmeryar-accounting::inventory.form.quantity'))
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('cost_price')
                                    ->label(__('jmeryar-accounting::inventory.form.cost_price'))
                                    ->numeric()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('unit_price')
                                    ->label(__('jmeryar-accounting::inventory.form.unit_price'))
                                    ->numeric(),
                            ])
                            ->columnSpan(2),
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Section::make()->schema([
                                    Forms\Components\DatePicker::make('expiry_date')
                                        ->label(__('jmeryar-accounting::inventory.form.expiry_date')),
                                ]),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label(__('jmeryar-accounting::inventory.table.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('jmeryar-accounting::inventory.table.quantity'))
                    ->searchable()
                    ->summarize(Sum::make()),
                Tables\Columns\TextColumn::make('cost_price')
                    ->label(__('jmeryar-accounting::inventory.table.cost_price'))
                    ->summarize(Summarizer::make()
                        ->label('Total Cost')
                        ->using(fn(Builder $query): string => (string) $query->sum(DB::raw('cost_price * quantity')) / 100)),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label(__('jmeryar-accounting::inventory.table.unit_price'))
                    ->summarize(Summarizer::make()
                        ->label('Total Value')
                        ->using(fn(Builder $query): string => (string) $query->sum(DB::raw('unit_price * quantity')) / 100)),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->label(__('jmeryar-accounting::inventory.table.expiry_date'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('jmeryar-accounting::inventory.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('jmeryar-accounting::inventory.table.updated_at'))
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

<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Table;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Forms\Components\Field\MoneyInput;
use Xoshbin\JmeryarAccounting\Models\InventoryBatch;

class InventoryResource extends Resource
{
    protected static ?string $model = InventoryBatch::class;

    //    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Inventory';

    public static function getNavigationLabel(): string
    {
        return __('jmeryar-accounting::inventory.title');
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
                                Forms\Components\Select::make('product_id')
                                    ->label(__('jmeryar-accounting::inventory.form.name'))
                                    ->relationship('product', 'name')
                                    ->required(),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->label(__('jmeryar-accounting::inventory.form.quantity'))
                                    ->maxLength(255),
                                MoneyInput::make('cost_price')
                                    ->label(__('jmeryar-accounting::inventory.form.cost_price'))
                                    ->numeric()
                                    ->maxLength(255),
                                MoneyInput::make('unit_price')
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

    public static function table(Table $table): Table
    {
        return $table
            ->defaultGroup('product.name')
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
            'index' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\InventoryResource\Pages\ListInventories::route('/'),
            'create' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\InventoryResource\Pages\CreateInventory::route('/create'),
            'edit' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\InventoryResource\Pages\EditInventory::route('/{record}/edit'),
        ];
    }
}

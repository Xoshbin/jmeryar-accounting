<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources;

use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Xoshbin\JmeryarAccounting\Models\Product;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    //    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Inventory';

    public static function getNavigationLabel(): string
    {
        return __('jmeryar-accounting::products.title');
    }

    public static function form(Form $form): Form
    {
        return
            $form
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Section::make()
                                ->columns(3)
                                ->schema([
                                    Forms\Components\Grid::make()
                                        ->columns(3)
                                        ->schema([
                                            Forms\Components\Radio::make('type')
                                                ->label(__('jmeryar-accounting::products.form.type'))
                                                ->options([
                                                    'Product' => 'Product',
                                                    'Service' => 'Service',
                                                ])
                                                ->default('Product')
                                                ->inline()
                                                ->required(),
                                        ]),
                                    Forms\Components\TextInput::make('name')
                                        ->label(__('jmeryar-accounting::products.form.name'))
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('sku')
                                        ->label(__('jmeryar-accounting::products.form.sku'))
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\Select::make('category_id')
                                        ->label(__('jmeryar-accounting::products.form.category'))
                                        ->relationship('category', 'name')
                                        ->required(),
                                    Forms\Components\Textarea::make('description')
                                        ->label(__('jmeryar-accounting::products.form.description'))
                                        ->columnSpanFull(),
                                ])
                                ->columnSpan(2),
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\Section::make()->schema([
                                        SpatieMediaLibraryFileUpload::make('image')
                                            ->collection('products')
                                            ->downloadable()
                                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                                            ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('name')
                    ->label(__('jmeryar-accounting::products.table.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label(__('jmeryar-accounting::products.table.sku'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('jmeryar-accounting::products.table.category'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('jmeryar-accounting::products.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('jmeryar-accounting::products.table.updated_at'))
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
            \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\ProductResource\RelationManagers\InventoryBatchesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\ProductResource\Pages\ListProducts::route('/'),
            'create' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\ProductResource\Pages\CreateProduct::route('/create'),
            'edit' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\ProductResource\Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}

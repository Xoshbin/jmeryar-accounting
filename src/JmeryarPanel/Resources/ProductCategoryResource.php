<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Xoshbin\JmeryarAccounting\Models\ProductCategory;

class ProductCategoryResource extends Resource
{
    protected static ?string $model = ProductCategory::class;

    //    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Inventory';

    public static function getNavigationLabel(): string
    {
        return __('jmeryar-accounting::product_categories.title');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('jmeryar-accounting::product_categories.form.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label(__('jmeryar-accounting::product_categories.form.description'))
                    ->columnSpanFull(),
                Forms\Components\Select::make('parent_id')
                    ->label(__('jmeryar-accounting::product_categories.form.parent_id'))
                    ->relationship('parent', 'name'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('jmeryar-accounting::product_categories.table.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label(__('jmeryar-accounting::product_categories.table.parent_name'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('jmeryar-accounting::product_categories.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('jmeryar-accounting::product_categories.table.updated_at'))
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
            \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\ProductCategoryResource\RelationManagers\ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\ProductCategoryResource\Pages\ListProductCategories::route('/'),
            'create' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\ProductCategoryResource\Pages\CreateProductCategory::route('/create'),
            'edit' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\ProductCategoryResource\Pages\EditProductCategory::route('/{record}/edit'),
        ];
    }
}

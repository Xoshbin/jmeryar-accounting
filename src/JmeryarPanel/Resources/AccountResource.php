<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Xoshbin\JmeryarAccounting\Models\Account;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    //    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Accounting';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('jmeryar-accounting::accounts.form.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->label(__('jmeryar-accounting::accounts.form.type'))
                    ->options([
                        'Asset' => __('jmeryar-accounting::accounts.form.asset'),
                        'Liability' => __('jmeryar-accounting::accounts.form.liability'),
                        'Equity' => __('jmeryar-accounting::accounts.form.equity'),
                        'Revenue' => __('jmeryar-accounting::accounts.form.revenue'),
                        'Expense' => __('jmeryar-accounting::accounts.form.expense'),
                    ]),
                Forms\Components\TextInput::make('code')
                    ->label(__('jmeryar-accounting::accounts.form.code'))
                    ->maxLength(5),
                Forms\Components\Select::make('parent_id')
                    ->label(__('jmeryar-accounting::accounts.form.parent_id'))
                    ->relationship('parent', 'name'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('jmeryar-accounting::accounts.table.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label(__('jmeryar-accounting::accounts.table.parent_name'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label(__('jmeryar-accounting::accounts.table.code'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('jmeryar-accounting::accounts.table.type')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('jmeryar-accounting::accounts.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('jmeryar-accounting::accounts.table.updated_at'))
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
            'index' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\AccountResource\Pages\ListAccounts::route('/'),
            'create' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\AccountResource\Pages\CreateAccount::route('/create'),
            'edit' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\AccountResource\Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}

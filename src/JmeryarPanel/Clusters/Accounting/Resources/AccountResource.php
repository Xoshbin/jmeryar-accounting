<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting;
use Xoshbin\JmeryarAccounting\Models\Account;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-document';

    protected static ?string $navigationGroup = 'Accounting';

    protected static ?string $cluster = Accounting::class;

    public static function getPluralLabel(): string
    {
        return __('jmeryar-accounting::accounts.title');
    }

    public static function getLabel(): string
    {
        return __('jmeryar-accounting::accounts.singular');
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
                                    Forms\Components\TextInput::make('name')
                                        ->label(__('jmeryar-accounting::accounts.form.name'))
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('code')
                                        ->label(__('jmeryar-accounting::accounts.form.code'))
                                        ->maxLength(5),
                                    Forms\Components\Select::make('parent_id')
                                        ->label(__('jmeryar-accounting::accounts.form.parent_id'))
                                        ->relationship('parent', 'name'),
                                ])
                                ->columnSpan(2),
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\Section::make()->schema([
                                        Forms\Components\Select::make('type')
                                            ->label(__('jmeryar-accounting::accounts.form.type'))
                                            ->options([
                                                'Asset' => __('jmeryar-accounting::accounts.form.asset'),
                                                'Liability' => __('jmeryar-accounting::accounts.form.liability'),
                                                'Equity' => __('jmeryar-accounting::accounts.form.equity'),
                                                'Revenue' => __('jmeryar-accounting::accounts.form.revenue'),
                                                'Expense' => __('jmeryar-accounting::accounts.form.expense'),
                                            ]),
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
            'index' => Accounting\Resources\AccountResource\Pages\ListAccounts::route('/'),
            'create' => Accounting\Resources\AccountResource\Pages\CreateAccount::route('/create'),
            'edit' => Accounting\Resources\AccountResource\Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}

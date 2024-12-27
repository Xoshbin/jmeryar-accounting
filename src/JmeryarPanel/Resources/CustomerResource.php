<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Xoshbin\JmeryarAccounting\Models\Customer;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    //    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Customers';

    public static function getNavigationLabel(): string
    {
        return __('jmeryar-accounting::customers.title');
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
                                    ->label(__('jmeryar-accounting::customers.form.name'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label(__('jmeryar-accounting::customers.form.email'))
                                    ->email()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->label(__('jmeryar-accounting::customers.form.phone'))
                                    ->tel()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('address')
                                    ->label(__('jmeryar-accounting::customers.form.address'))
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(2),
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Section::make()->schema([
                                    // TODO::You may list customer debts here just in numbers
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
                    ->label(__('jmeryar-accounting::customers.table.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('jmeryar-accounting::customers.table.email'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('jmeryar-accounting::customers.table.phone'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('jmeryar-accounting::customers.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('jmeryar-accounting::customers.table.updated_at'))
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
            \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\CustomerResource\RelationManagers\InvoicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\CustomerResource\Pages\ListCustomers::route('/'),
            'create' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\CustomerResource\Pages\CreateCustomer::route('/create'),
            'edit' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\CustomerResource\Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}

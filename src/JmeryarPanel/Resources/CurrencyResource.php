<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Xoshbin\JmeryarAccounting\Models\Currency;
use Xoshbin\JmeryarAccounting\Models\Setting;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    //    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Configuration';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Section::make()
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('symbol')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('currency_unit')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('currency_subunit')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columnSpan(2),
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Section::make()->schema([
                                    Forms\Components\Select::make('status')
                                        ->options([
                                            'Active' => 'Active',
                                            'Inactive' => 'Inactive',
                                        ])
                                        ->default('Active')
                                        ->required(),
                                ]),
                            ])
                            ->columnSpan(1),
                    ]),

                Forms\Components\Tabs::make('Exchange Rate Tab')
                    ->disabled(fn ($record) => Setting::first()?->currency->code === $record->code)
                    ->schema([
                        Forms\Components\Tabs\Tab::make('Exchange Rates')
                            ->badge(fn ($get) => count($get('exchangeRatesAsTarget') ?? []))
                            ->icon('heroicon-m-queue-list')
                            ->schema([
                                Forms\Components\Repeater::make('exchangeRatesAsTarget')
                                    ->hiddenLabel()
                                    ->label('Rates')
                                    ->relationship()
                                    ->columns(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('rate')
                                            ->label(function () {
                                                return Setting::first()?->currency->code.' per unit';
                                            })
                                            ->numeric()
                                            ->live(debounce: 600)
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                // Automatically calculate "Unit per Base Currency" when "Rate" is updated
                                                if ($state && $state > 0) {
                                                    // "Rate" now stores "IQD per USD", so we set "unit_per_base_currency" (USD per IQD)
                                                    $set('unit_per_base_currency', 1 / $state);
                                                }
                                            }),

                                        Forms\Components\TextInput::make('unit_per_base_currency')
                                            ->label(function () {
                                                return 'Unit per '.Setting::first()?->currency->code;
                                            })
                                            ->numeric()
                                            ->live(debounce: 300)
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                // Automatically calculate "Rate" when "Unit per Base Currency" is updated
                                                if ($state && $state > 0) {
                                                    // "Unit per Base Currency" stores "USD per IQD", so we set "rate" (IQD per USD)
                                                    $set('rate', 1 / $state);
                                                }
                                            })
                                            ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                                // Dynamically set the default value based on the "rate" field
                                                $rate = $get('rate');
                                                if ($rate && $rate > 0) {
                                                    // Set "Unit per Base Currency" when "Rate" exists
                                                    $set('unit_per_base_currency', 1 / $rate);
                                                }
                                            }),
                                        Forms\Components\DateTimePicker::make('created_at')
                                            ->default(now())
                                            ->label('Date and Time'),
                                    ])
                                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $record): array {
                                        $data['base_currency_id'] = Currency::first()->get('id');
                                        $data['target_currency_id'] = $record->id;

                                        return $data;
                                    }),
                            ]),
                    ])
                    ->columnSpan('full'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('symbol')
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency_unit')
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency_subunit')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status'),
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
            'index' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\CurrencyResource\Pages\ListCurrencies::route('/'),
            'create' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\CurrencyResource\Pages\CreateCurrency::route('/create'),
            'edit' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\CurrencyResource\Pages\EditCurrency::route('/{record}/edit'),
        ];
    }
}

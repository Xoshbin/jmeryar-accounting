<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Forms\Components\Field\MoneyInput;
use Xoshbin\JmeryarAccounting\Models\Currency;
use Xoshbin\JmeryarAccounting\Models\Setting;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $cluster = Accounting::class;

    public static function getPluralLabel(): ?string
    {
        return __('jmeryar-accounting::currencies.title');
    }

    public static function getLabel(): string
    {
        return __('jmeryar-accounting::currencies.singular');
    }

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
                                    ->label(__('jmeryar-accounting::currencies.form.code'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('name')
                                    ->label(__('jmeryar-accounting::currencies.form.name'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('symbol')
                                    ->label(__('jmeryar-accounting::currencies.form.symbol'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('currency_unit')
                                    ->label(__('jmeryar-accounting::currencies.form.currency_unit'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('currency_subunit')
                                    ->label(__('jmeryar-accounting::currencies.form.currency_subunit'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columnSpan(2),
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Section::make()->schema([
                                    Forms\Components\Select::make('status')
                                        ->label(__('jmeryar-accounting::currencies.form.status'))
                                        ->options([
                                            'Active' => __('jmeryar-accounting::currencies.form.status_active'),
                                            'Inactive' => __('jmeryar-accounting::currencies.form.status_inactive'),
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
                                        MoneyInput::make('unit_per_base_currency')
                                            ->label(function () {
                                                return __('jmeryar-accounting::currencies.form.unit_per_base_currency_label', ['currency' => Setting::first()?->currency->code]);
                                            })
                                            ->hint(function (Forms\Components\Component $component) {
                                                // Get the Livewire component instance
                                                $livewire = $component->getLivewire();

                                                // Access the main record
                                                $currentCurrency = $livewire->record->code;

                                                return '1 ' . Setting::first()?->currency->code . ' = x ' . $currentCurrency;
                                            })
                                            ->live(debounce: 600)
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                // Automatically calculate "Rate" when "Unit per Base Currency" is updated
                                                if ($state && $state > 0) {
                                                    // "Unit per Base Currency" stores "USD per IQD", so we set "rate" (IQD per USD)
                                                    $set('rate', 1 / $state);
                                                }
                                            }),

                                        MoneyInput::make('rate')
                                            ->label(function () {
                                                return __('jmeryar-accounting::currencies.form.rate_label', ['currency' => Setting::first()?->currency->code]);
                                            })
                                            ->hint(function (Forms\Components\Component $component) {
                                                // Get the Livewire component instance
                                                $livewire = $component->getLivewire();

                                                // Access the main record
                                                $currentCurrency = $livewire->record->code;

                                                return '1 ' . $currentCurrency . ' = x ' . Setting::first()?->currency->code;
                                            })
                                            ->live(debounce: 300)
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                // Automatically calculate "Unit per Base Currency" when "Rate" is updated
                                                if ($state && $state > 0) {
                                                    // "Rate" now stores "IQD per USD", so we set "unit_per_base_currency" (USD per IQD)
                                                    $set('unit_per_base_currency', 1 / $state);
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
                                            ->label(__('jmeryar-accounting::currencies.form.created_at'))
                                            ->default(now())
                                            ->label('Date and Time'),
                                    ])
                                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $record): array {
                                        $data['base_currency_id'] = Setting::first()?->currency->id;
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
                    ->label(__('jmeryar-accounting::currencies.table.code'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('jmeryar-accounting::currencies.table.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('symbol')
                    ->label(__('jmeryar-accounting::currencies.table.symbol'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency_unit')
                    ->label(__('jmeryar-accounting::currencies.table.currency_unit'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency_subunit')
                    ->label(__('jmeryar-accounting::currencies.table.currency_subunit'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('jmeryar-accounting::currencies.table.status')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('jmeryar-accounting::currencies.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('jmeryar-accounting::currencies.table.updated_at'))
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
            'index' => Accounting\Resources\CurrencyResource\Pages\ListCurrencies::route('/'),
            'create' => Accounting\Resources\CurrencyResource\Pages\CreateCurrency::route('/create'),
            'edit' => Accounting\Resources\CurrencyResource\Pages\EditCurrency::route('/{record}/edit'),
        ];
    }
}

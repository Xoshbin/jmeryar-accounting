<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Clusters\Accounting;
use Xoshbin\JmeryarAccounting\Models\Tax;

class TaxResource extends Resource
{
    protected static ?string $model = Tax::class;

    protected static ?string $navigationIcon = 'heroicon-o-percent-badge';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $cluster = Accounting::class;

    public static function getPluralLabel(): string
    {
        return __('jmeryar-accounting::taxes.title');
    }

    public static function getLabel(): string
    {
        return __('jmeryar-accounting::taxes.singular');
    }

    public static function form(Form $form): Form
    {
        $itemModel = $form->getRecord();

        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Section::make()
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('jmeryar-accounting::taxes.form.name'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('tax_computation')
                                    ->label(__('jmeryar-accounting::taxes.form.tax_computation'))
                                    ->options([
                                        'Fixed' => __('jmeryar-accounting::taxes.form.fixed'),
                                        'Percentage' => __('jmeryar-accounting::taxes.form.percentage'),
                                        'Percentage_inclusive' => __('jmeryar-accounting::taxes.form.percentage_inclusive'),
                                    ])
                                    ->default('Percentage')
                                    ->live()
                                    ->required(),
                                Forms\Components\Select::make('type')
                                    ->label(__('jmeryar-accounting::taxes.form.type'))
                                    ->options([
                                        'Sales' => __('jmeryar-accounting::taxes.form.sales'),
                                        'Purchases' => __('jmeryar-accounting::taxes.form.purchases'),
                                        'None' => __('jmeryar-accounting::taxes.form.none'),
                                    ])
                                    ->required(),
                                Forms\Components\Select::make('tax_scope')
                                    ->label(__('jmeryar-accounting::taxes.form.tax_scope'))
                                    ->options([
                                        'Goods' => __('jmeryar-accounting::taxes.form.goods'),
                                        'Services' => __('jmeryar-accounting::taxes.form.services'),
                                    ])
                                    ->required(),
                            ])
                            ->columnSpan(2),
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Section::make()->schema([
                                    Forms\Components\TextInput::make('amount')
                                        ->label(__('jmeryar-accounting::taxes.form.amount'))
                                        ->postfix(function (callable $get) {
                                            if ($get('tax_computation') !== 'Fixed') {
                                                return '%';
                                            }
                                        })
                                        ->required(fn ($get) => $get('tax_computation') !== 'Group')
                                        ->numeric(),
                                ])
                                    ->visible(fn ($get) => $get('tax_computation') !== 'Group'),

                                Forms\Components\Section::make()->schema([
                                    Forms\Components\Select::make('status')
                                        ->label(__('jmeryar-accounting::taxes.form.status'))
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

                // TODO:: right now the reapeater not supporting belongs to and hasmany relation
                // it's not working for the attach and associate too
                //                Forms\Components\Grid::make()
                //                    ->schema([
                //                        Forms\Components\Tabs::make('Taxes')
                //                            ->columns(1)
                //                            ->visible(fn($get) => $get('tax_computation') === 'Group') // Only show if parent is selected
                //                            ->schema([
                //                                Forms\Components\Tabs\Tab::make('Taxes')
                //                                    ->badge(fn($get) => count($get('children') ?? []))
                //                                    ->icon('heroicon-m-queue-list')
                //                                    ->schema([
                //                                        Forms\Components\Repeater::make('children')
                //                                            ->label('Child Taxes')
                //                                            ->hiddenLabel()
                //                                            ->relationship() // Reference to the child relationship
                //                                            ->schema([
                //                                                Forms\Components\Select::make('name')
                //                                                    ->label('Tax')
                //                                                    ->columnSpan(2)
                //                                                    ->relationship('children', 'name')
                //                                                    ->searchable()
                //                                                    ->preload()
                //                                                    ->afterStateUpdated(function ($state, callable $set) use ($itemModel) {
                //                                                        // Ensure the parent_id is updated in the database correctly
                //                                                        if ($state) {
                //                                                            $set('parent_id', $itemModel->id);
                //                                                        }
                //                                                    })
                //                                                    ->createOptionForm([
                //                                                        Forms\Components\TextInput::make('name')
                //                                                            ->required()
                //                                                            ->maxLength(255),
                //                                                        Forms\Components\Select::make('tax_computation')
                //                                                            ->options([
                //                                                                'Fixed' => 'Fixed',
                //                                                                'Percentage' => 'Percentage',
                //                                                                'Group' => 'Group',
                //                                                                'Percentage_inclusive' => 'Percentage_inclusive',
                //                                                            ])
                //                                                            ->live()
                //                                                            ->required(),
                //                                                        Forms\Components\TextInput::make('amount')
                //                                                            ->required()
                //                                                            ->numeric(),
                //                                                        Forms\Components\Select::make('type')
                //                                                            ->options([
                //                                                                'Sales' => 'Sales',
                //                                                                'Purchases' => 'Purchases',
                //                                                                'None' => 'None'
                //                                                            ])
                //                                                            ->required(),
                //                                                        Forms\Components\Select::make('tax_scope')
                //                                                            ->options([
                //                                                                'Goods' => 'Goods',
                //                                                                'Services' => 'Services'
                //                                                            ])
                //                                                            ->required(),
                //                                                        Forms\Components\Select::make('parent_id')
                //                                                            ->label('Parent Tax')
                //                                                            ->relationship('parent', 'name') // Select parent tax
                //                                                            ->nullable()
                //                                                            ->helperText('If this is a child tax, select a parent tax'),
                //                                                        Forms\Components\Select::make('status')
                //                                                            ->options([
                //                                                                'Active' => 'Active',
                //                                                                'Inactive' => 'Inactive'
                //                                                            ])
                //                                                            ->required(),
                //                                                    ]),
                //                                            ])
                //                                            ->columns(1)
                //                                            ->defaultItems(0)
                //                                            ->cloneable(),
                //                                    ]),
                //                            ])
                //                            ->columnSpan('full'),
                //                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('jmeryar-accounting::taxes.table.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('tax_computation')
                    ->label(__('jmeryar-accounting::taxes.table.tax_computation'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('jmeryar-accounting::taxes.table.amount'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('jmeryar-accounting::taxes.table.type'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('tax_scope')
                    ->label(__('jmeryar-accounting::taxes.table.tax_scope'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('jmeryar-accounting::taxes.table.status'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('jmeryar-accounting::taxes.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('jmeryar-accounting::taxes.table.updated_at'))
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
            'index' => Accounting\Resources\TaxResource\Pages\ListTaxes::route('/'),
            'create' => Accounting\Resources\TaxResource\Pages\CreateTax::route('/create'),
            'edit' => Accounting\Resources\TaxResource\Pages\EditTax::route('/{record}/edit'),
        ];
    }
}

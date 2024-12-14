<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources;

use Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\TaxResource\Pages;
use Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\TaxResource\RelationManagers;
use Xoshbin\JmeryarAccounting\Models\Tax;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TaxResource extends Resource
{
    protected static ?string $model = Tax::class;

//    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Configuration';

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
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('tax_computation')
                                    ->options([
                                        'Fixed' => 'Fixed',
                                        'Percentage' => 'Percentage',
//                                        'Group' => 'Group',
                                        'Percentage_inclusive' => 'Percentage_inclusive',
                                    ])
                                    ->default('Percentage')
                                    ->live()
                                    ->required(),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'Sales' => 'Sales',
                                        'Purchases' => 'Purchases',
                                        'None' => 'None'
                                    ])
                                    ->required(),
                                Forms\Components\Select::make('tax_scope')
                                    ->options([
                                        'Goods' => 'Goods',
                                        'Services' => 'Services'
                                    ])
                                    ->required(),
                            ])
                            ->columnSpan(2),
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Section::make()->schema([
                                    Forms\Components\TextInput::make('amount')
                                        ->postfix(function(callable $get){
                                            if ($get('tax_computation') !== 'Fixed'){
                                                return '%';
                                            }
                                        } )
                                        ->required(fn($get) => $get('tax_computation') !== 'Group')
                                        ->numeric(),
                                ])
                                ->visible(fn($get) => $get('tax_computation') !== 'Group'),

                                Forms\Components\Section::make()->schema([
                                    Forms\Components\Select::make('status')
                                        ->options([
                                            'Active' => 'Active',
                                            'Inactive' => 'Inactive'
                                        ])
                                        ->default('Active')
                                        ->required(),
                                ]),
                            ])
                            ->columnSpan(1),
                    ]),

                    //TODO:: right now the reapeater not supporting belongs to and hasmany relation
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
                    ->searchable(),
                Tables\Columns\TextColumn::make('tax_computation')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tax_scope')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->numeric()
                    ->sortable(),
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
            'index' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\TaxResource\Pages\ListTaxes::route('/'),
            'create' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\TaxResource\Pages\CreateTax::route('/create'),
            'edit' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\TaxResource\Pages\EditTax::route('/{record}/edit'),
        ];
    }
}

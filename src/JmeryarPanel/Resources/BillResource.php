<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources;

use Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\BillResource\Pages;
use Xoshbin\JmeryarAccounting\Models\Bill;
use Xoshbin\JmeryarAccounting\Models\Currency;
use Xoshbin\JmeryarAccounting\Models\Payment;
use Xoshbin\JmeryarAccounting\Models\Product;
use Xoshbin\JmeryarAccounting\Models\Setting;
use Xoshbin\JmeryarAccounting\Models\Supplier;
use Xoshbin\JmeryarAccounting\Models\Tax;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class BillResource extends Resource
{
    protected static ?string $model = Bill::class;

//    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Vendors';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        // Left Side: Bill Details and Customer Details
                        Forms\Components\Grid::make(2) // Takes up two-thirds of the width
                        ->schema([
                            Forms\Components\Section::make('Bill Details')
                                ->schema([
                                    Forms\Components\TextInput::make('bill_number')
                                        ->label('Bill Number')
                                        ->required()
                                        ->default(self::getNextBillNumber())
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(255),
                                    Forms\Components\DatePicker::make('bill_date')
                                        ->label('Bill Date')
                                        ->default(now())
                                        ->required(),
                                ])
                                ->columns(2),

                            Forms\Components\Section::make('Supplier Details')
                                ->schema([
                                    Forms\Components\Select::make('supplier_id')
                                        ->label('Supplier')
                                        ->relationship('supplier', 'name')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->createOptionUsing(function (array $data) {
                                            return Supplier::create(array_merge($data));
                                        })
                                        ->createOptionForm([
                                            Forms\Components\Grid::make()->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->required()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('email')
                                                    ->email()
                                                    ->maxLength(255),
                                            ]),
                                            Forms\Components\Grid::make()->schema([
                                                Forms\Components\TextInput::make('phone')
                                                    ->tel()
                                                    ->maxLength(255),
                                            ]),
                                            Forms\Components\Textarea::make('address')
                                                ->columnSpanFull(),
                                        ]),
                                    Forms\Components\Select::make('status')
                                        ->label('Status')
                                        ->default('Draft')
                                        ->options([
                                            'Draft' => 'Draft',
                                            'Sent' => 'Sent',
                                            'Partial' => 'Partial',
                                            'Paid' => 'Paid',
                                        ])
                                        ->required(),
                                ])
                                ->columns(2),

                            Forms\Components\Section::make('Note')
                                ->schema([
                                    Forms\Components\Textarea::make('note')
                                        ->hiddenLabel()
                                        ->label('Note'),
                                ])
                                ->columns(1),
                        ])
                            ->columnSpan(2), // Left side takes two-thirds of the grid

                        // Right Side: Payments Section
                        Forms\Components\Grid::make(1) // Takes up one-third of the width
                        ->schema([
                            Forms\Components\Section::make('')
                                ->hiddenLabel()
                                ->schema([
                                    Forms\Components\TextInput::make('untaxed_amount')
                                    ->label('Untaxed Amount')
                                    ->numeric()
                                    ->readOnly(),
                                    Forms\Components\TextInput::make('tax_amount')
                                        ->label('Tax')
                                        ->readOnly(),
                                    Forms\Components\TextInput::make('total_amount')
                                        ->label('Total Amount')
                                        ->numeric()
                                        ->readOnly(),
                                ]),
                            Forms\Components\Section::make()
                                ->hiddenLabel()
                                ->schema([
                                    Forms\Components\TextInput::make('total_paid_amount')
                                        ->label('Total Paid Amount')
                                        ->formatStateUsing(fn($state, $record) => $record->total_paid_amount ?? 0)
                                        ->readOnly(),
                                    Forms\Components\TextInput::make('amount_due')
                                        ->label('Amount Due')
                                        ->formatStateUsing(fn($state, $record) => ($record->total_amount ?? 0) - ($record->total_paid_amount ?? 0))
                                        ->readOnly(),
                                    Forms\Components\DatePicker::make('due_date')
                                        ->label('Due Date')
                                        ->nullable(),
                                    Forms\Components\Select::make('currency_id')
                                        ->label('Currency')
                                        ->default(fn() => Setting::first()?->currency->id)
                                        ->relationship('currency', 'code')
                                        ->disabled(fn($record) => $record?->status !== 'Draft' && $record !== null)
                                ])
                        ])
                            ->columnSpan(1), // Right side takes one-third of the grid
                    ]),

                // Tabs for Bills and Payments, placed outside the left-right split layout
                Forms\Components\Tabs::make('Bill Tabs')
                    ->schema([
                        Forms\Components\Tabs\Tab::make('Bill Items')
                            ->badge(fn($get) => count($get('billItems') ?? []))
                            ->icon('heroicon-m-queue-list')
                            ->schema([
                                Forms\Components\Repeater::make('billItems')
                                    ->hiddenLabel()
                                    ->label('Items')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\Select::make('product_id')
                                            ->label('Product')
                                            ->columnSpan(2)
                                            ->relationship('product', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->createOptionUsing(function (array $data) {
                                                return Product::create(array_merge($data));
                                            })
                                            ->createOptionForm([
                                                Forms\Components\Grid::make()->schema([
                                                    Forms\Components\TextInput::make('name')
                                                        ->required()
                                                        ->maxLength(255),
                                                    Forms\Components\TextInput::make('sku')
                                                        ->label('SKU')
                                                        ->required()
                                                        ->maxLength(255),
                                                    Forms\Components\Select::make('category_id')
                                                        ->relationship('category', 'name')
                                                        ->required(),
                                                ]),
                                                Forms\Components\Textarea::make('description')
                                                    ->columnSpanFull(),
                                            ]),
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->columnSpan(1)
                                            ->numeric()
                                            ->live(debounce: 600)
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                $taxId = $get('taxes');
                                                $tax = $taxId ? Tax::find($taxId) : null;
                                                $unitPrice = $get('cost_price') ?? 0;
                                                $set('total_cost', self::calculateTotalPerRow($state, $unitPrice, $tax));
                                            }),
                                        Forms\Components\TextInput::make('cost_price')
                                            ->label('Cost Price')
                                            ->columnSpan(1)
                                            ->numeric()
                                            ->live(debounce: 600)
                                            ->required(fn($get) => $get('quantity') > 0)
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                $taxId = $get('taxes');
                                                $tax = $taxId ? Tax::find($taxId) : null;
                                                $quantity = $get('quantity') ?? 0;
                                                $set('total_cost', self::calculateTotalPerRow($quantity, $state, $tax));
                                            }),
                                        Forms\Components\TextInput::make('unit_price')
                                            ->label('Unit Price')
                                            ->columnSpan(1)
                                            ->default(fn($get) => Product::find($get('product_id'))?->unit_price ?? null)
                                            ->numeric(),
                                        Forms\Components\Select::make('tax_id')
                                            ->label(function ($state, callable $get) {
                                                if ($state !== null) {
                                                    $tax = $state ? Tax::find($state) : 0;
                                                    if ($tax instanceof Collection) {
                                                        return 'Tax ' . ($get('cost_price') * $get('quantity')) * ($tax->first()->amount / 100);
                                                    } else {
                                                        if ($tax){
                                                            return 'Tax ' . ($get('cost_price') * $get('quantity')) * ($tax->amount / 100);
                                                        }
                                                    }
                                                } else {
                                                    return 'Tax';
                                                }
                                            })
                                            ->relationship('taxes', 'name')
                                            ->preload()
                                            ->live(debounce: 600)
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                $tax = $state ? Tax::find($state) : null;
                                                $quantity = $get('quantity') ?? 0;
                                                $costPrice = $get('cost_price') ?? 0;
                                                $totalCostPerRow = self::calculateTotalPerRow($quantity, $costPrice, $tax);
                                                $taxAmountPerRow = self::calculateTaxPerRow($get('cost_price'), $get('quantity'), $tax->amount);

                                                $set('total_cost', $totalCostPerRow);
                                                $set('tax_amount', $taxAmountPerRow);
                                                $set('untaxed_amount', $totalCostPerRow - $taxAmountPerRow);
                                            }),
                                        Forms\Components\Hidden::make('tax_amount'),
                                        Forms\Components\Hidden::make('untaxed_amount'),
                                        Forms\Components\TextInput::make('total_cost')
                                            ->label('Total Cost')
                                            ->columnSpan(1)
                                            ->required(fn($get) => $get('quantity') > 0)
                                            ->numeric(),
                                    ])
                                    ->defaultItems(0)
                                    ->columns(7)
                                    ->cloneable()
                                    ->live(debounce: 600)
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        // Calculate and set total amount
                                        // TODO: Fix delay in updating the total amount; it updates only after adding the next item.
                                        $totalUntaxedAmount = collect($state)->sum(fn($item) => $item['untaxed_amount'] ?? 0);
                                        $totalTaxAmount = collect($state)->sum(fn($item) => $item['tax_amount'] ?? 0);
                                        $totalAmount = collect($state)->sum(fn($item) => $item['total_cost'] ?? 0);

                                        $set('total_amount', $totalAmount);
                                        $set('tax_amount', $totalTaxAmount);
                                        $set('untaxed_amount', $totalUntaxedAmount);
                                    }),
                            ]),
                        Forms\Components\Tabs\Tab::make('Bill Payments')
                            ->badge(fn($get) => count($get('payments') ?? []))
                            ->icon('heroicon-m-banknotes')
                            ->schema([
                                Forms\Components\Repeater::make('payments')
                                    ->hiddenLabel()
                                    ->label('Payments')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\DatePicker::make('payment_date')
                                            ->label('Payment Date')
                                            ->default(now()),
                                        Forms\Components\Select::make('currency_id')
                                            ->label('Currency')
                                            ->relationship('currency', 'code')
                                            ->disabled(fn($record) => $record?->status === 'Paid' && $record !== null)
                                            ->live(debounce: 600)
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                $currency = Currency::find($state);
                                                $invoiceCurrency = Currency::find($get('../../currency_id'));
                                                $rate = $currency->code === $invoiceCurrency->code ? 1 : $currency->exchangeRatesAsTarget->first()->rate;
                                                $set('exchange_rate', $rate ?? 0);
                                            }),
                                        Forms\Components\TextInput::make('amount')
                                            ->label('Amount')
                                            ->numeric()
                                            ->live(debounce: 600)
                                            ->postfix('*')
                                            ->required()
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                $exchange_rate = $get('exchange_rate') ?? 0;
                                                $set('amount_in_invoice_currency', $exchange_rate * $state);
                                            }),
                                        Forms\Components\TextInput::make('exchange_rate')
                                            ->numeric()
                                            ->required(),
                                        Forms\Components\TextInput::make('amount_in_invoice_currency')
                                            ->numeric()
                                            ->required()
                                            ->prefix('='),
                                        Forms\Components\Select::make('payment_method')
                                            ->label('Payment Method')
                                            ->options([
                                                'Cash' => 'Cash',
                                                'Bank' => 'Bank',
                                                'Credit Card' => 'Credit Card',
                                            ])
                                            ->required(fn($get) => $get('amount') > 0),
                                    ])
                                    ->defaultItems(0)
                                    ->columns(6)
                                    ->cloneable()
                                    ->live(debounce: 600)
                                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                        $data['payment_type'] = Payment::TYPE_EXPENSE;

                                        return $data;
                                    })
                                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                        $totalPaidAmount = collect($state)->sum(fn($item) => $item['amount_in_invoice_currency'] ?? 0);
                                        $set('total_paid_amount', $totalPaidAmount);
                                        $set('amount_due', $get('total_amount') - $totalPaidAmount);
                                    })
                                    ->itemLabel(fn(array $state): ?string => $state['payment_date'] . ' ' . $state['amount_in_invoice_currency'] ?? null),
                            ])
                    ])
                    ->columnSpan('full'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bill_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bill_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                ->badge(),
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

    protected static function calculateTotalPerRow($quantity, $unitPrice, $tax): float
    {
        $basePrice = $quantity * $unitPrice;
        if ($tax) {
            if ($tax->tax_computation === 'Percentage') {
                return $basePrice + ($basePrice * ($tax->amount / 100));
            } elseif ($tax->tax_computation === 'Fixed') {
                return $basePrice + $tax->amount;
            }
        }
        return $basePrice;
    }

    protected static function calculateTaxPerRow($costPrice, $quantity, $taxAmount): int
    {
        return ($costPrice * $quantity) * ($taxAmount / 100);
    }

    protected static function getNextBillNumber(): string
    {
        $lastBill = Bill::latest('id')->first();
        $newNumber = $lastBill ? intval(substr($lastBill->bill_number, -4)) + 1 : 1;
        return 'BILL-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public static function getRelations(): array
    {
        return [
//            RelationManagers\BillItemsRelationManager::class,
//            RelationManagers\PaymentsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\BillResource\Pages\ListBills::route('/'),
            'create' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\BillResource\Pages\CreateBill::route('/create'),
            'edit' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\BillResource\Pages\EditBill::route('/{record}/edit'),
        ];
    }
}

<?php

namespace Xoshbin\JmeryarAccounting\JmeryarPanel\Resources;

use Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\InvoiceResource\Pages;
use Xoshbin\JmeryarAccounting\Models\Currency;
use Xoshbin\JmeryarAccounting\Models\Customer;
use Xoshbin\JmeryarAccounting\Models\Invoice;
use Xoshbin\JmeryarAccounting\Models\Payment;
use Xoshbin\JmeryarAccounting\Models\Product;
use Xoshbin\JmeryarAccounting\Models\Setting;
use Xoshbin\JmeryarAccounting\Models\Tax;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

//    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Customers';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        // Left Side: Invoice Details and Customer Details
                        Forms\Components\Grid::make(2) // Takes up two-thirds of the width
                        ->schema([
                            Forms\Components\Section::make('Invoice Details')
                                ->schema([
                                    Forms\Components\TextInput::make('invoice_number')
                                        ->label('Invoice Number')
                                        ->required()
                                        ->default(self::getNextInvoiceNumber())
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(255),
                                    Forms\Components\DatePicker::make('invoice_date')
                                        ->label('Invoice Date')
                                        ->default(now())
                                        ->required(),
                                ])
                                ->columns(2),

                            Forms\Components\Section::make('Customer Details')
                                ->schema([
                                    Forms\Components\Select::make('customer_id')
                                        ->label('Customer')
                                        ->relationship('customer', 'name')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->createOptionUsing(function (array $data) {
                                            return Customer::create(array_merge($data));
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
                            Forms\Components\Section::make('')
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

                // Tabs for Invoices and Payments, placed outside the left-right split layout
                Forms\Components\Tabs::make('Invoice Tabs')
                    ->schema([
                        Forms\Components\Tabs\Tab::make('Invoice Items')
                            ->badge(fn($get) => count($get('invoiceItems') ?? []))
                            ->icon('heroicon-m-queue-list')
                            ->schema([
                                Forms\Components\Repeater::make('invoiceItems')
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
                                            ])
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                if ($state) {
                                                    $product = null;

                                                    if ($state instanceof Collection) {
                                                        // If $state is a collection, use the first product's ID
                                                        $product = $state->first();
                                                    } elseif ($state instanceof Product) {
                                                        // If $state is already a Product instance, use it directly
                                                        $product = $state;
                                                    } else {
                                                        // If $state is an ID, find the Product by ID
                                                        $product = Product::find($state);
                                                    }
                                                    if ($product) {
                                                        // Get the latest inventory batch for the product
                                                        $latestBatch = $product->inventoryBatches()->orderBy('created_at', 'asc')->first();

                                                        if ($latestBatch) {
                                                            // Set default values based on inventory batch
                                                            $quantity = $latestBatch->quantity ?? 0;
                                                            $unitPrice = $latestBatch->unit_price ?? 0;

                                                            $set('quantity', $quantity);
                                                            $set('unit_price', $unitPrice);

                                                            // Calculate and set total price
                                                            $totalPrice = $quantity * $unitPrice;
                                                            $set('total_price', $totalPrice);

                                                            // Update total amount if part of a larger group
                                                            $parentTotalAmount = $get('../../total_amount') ?? 0;
                                                            $set('../../total_amount', $parentTotalAmount + $totalPrice);
                                                        }
                                                    }
                                                }
                                            }),
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->columnSpan(1)
                                            ->numeric()
                                            ->live(debounce: 600)
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                $taxId = $get('taxes');
                                                $tax = $taxId ? Tax::find($taxId) : null;
                                                $unitPrice = $get('unit_price') ?? 0;
                                                $set('total_price', self::calculateTotalPerRow($state, $unitPrice, $tax));
                                            }),
                                        Forms\Components\TextInput::make('unit_price')
                                            ->label('Unit Price')
                                            ->columnSpan(1)
                                            ->numeric()
                                            ->live(debounce: 600)
                                            ->required(fn($get) => $get('quantity') > 0)
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                $taxId = $get('taxes');
                                                $tax = $taxId ? Tax::find($taxId) : null;
                                                $quantity = $get('quantity') ?? 0;
                                                $set('total_price', self::calculateTotalPerRow($quantity, $state, $tax));
                                            }),
                                        Forms\Components\Select::make('tax_id')
                                            ->label(function ($state, callable $get) {
                                                if ($state !== null) {
                                                    $tax = $state ? Tax::find($state) : null;

                                                    if ($tax instanceof Collection) {
                                                        $taxAmount = optional($tax->first())->amount; // Safely access 'amount'
                                                    } else {
                                                        $taxAmount = optional($tax)->amount; // Safely access 'amount' on the single Tax
                                                    }

                                                    if ($taxAmount !== null) {
                                                        $totalTax = ($get('unit_price') * $get('quantity')) * ($taxAmount / 100);
                                                        return 'Tax ' . $totalTax;
                                                    } else {
                                                        return 'Tax 0'; // Default value when tax amount is null
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
                                                $costPrice = $get('unit_price') ?? 0;
                                                $totalCostPerRow = self::calculateTotalPerRow($quantity, $costPrice, $tax);
                                                $taxAmountPerRow = self::calculateTaxPerRow($get('unit_price'), $get('quantity'), $tax->amount);

                                                $set('total_price', $totalCostPerRow);
                                                $set('tax_amount', $taxAmountPerRow);
                                                $set('untaxed_amount', $totalCostPerRow - $taxAmountPerRow);
                                            }),
                                        Forms\Components\Hidden::make('tax_amount'),
                                        Forms\Components\Hidden::make('untaxed_amount'),
                                        Forms\Components\TextInput::make('total_price')
                                            ->label('Total Price')
                                            ->numeric(),
                                    ])
                                    ->defaultItems(0)
                                    ->columns(6)
                                    ->cloneable()
                                    ->live()
                                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                        // Calculate and set total amount
                                        // TODO: Fix delay in updating the total amount; it updates only after adding the next item.
                                        $totalUntaxedAmount = collect($state)->sum(fn($item) => $item['untaxed_amount'] ?? 0);
                                        $totalTaxAmount = collect($state)->sum(fn($item) => $item['tax_amount'] ?? 0);
                                        $totalAmount = collect($state)->sum(fn($item) => $item['total_price'] ?? 0);

                                        $set('total_amount', $totalAmount);
                                        $set('tax_amount', $totalTaxAmount);
                                        $set('untaxed_amount', $totalUntaxedAmount);
                                    }),
                            ]),
                        Forms\Components\Tabs\Tab::make('Invoice Payments')
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
                                            ->default(now())
                                            ->required(),
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
                                        $data['payment_type'] = Payment::TYPE_INCOME;

                                        return $data;
                                    })
                                    ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                        // Calculate and set total paid amount
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
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
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

    private static function calculateTaxPerRow($costPrice, $quantity, $taxAmount): int
    {
        return ($costPrice * $quantity) * ($taxAmount / 100);
    }

    protected static function getNextInvoiceNumber(): string
    {
        $lastInvoice = Invoice::latest('id')->first();
        $newNumber = $lastInvoice ? intval(substr($lastInvoice->invoice_number, -4)) + 1 : 1;
        return 'INV-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public static function getRelations(): array
    {
        return [
//            RelationManagers\InvoiceItemsRelationManager::class,
//            RelationManagers\PaymentsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\InvoiceResource\Pages\ListInvoices::route('/'),
            'create' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\InvoiceResource\Pages\CreateInvoice::route('/create'),
            'edit' => \Xoshbin\JmeryarAccounting\JmeryarPanel\Resources\InvoiceResource\Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}

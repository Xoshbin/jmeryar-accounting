<?php

namespace Xoshbin\JmeryarAccounting\Models;

use Xoshbin\JmeryarAccounting\Database\Factories\InvoiceItemFactory;
use Xoshbin\JmeryarAccounting\Observers\InvoiceItemObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Xoshbin\JmeryarAccounting\Casts\MoneyCast;

#[ObservedBy([InvoiceItemObserver::class])]
class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'untaxed_amount',
        'tax_amount'
    ];

    protected $casts = [
        'unit_price' => MoneyCast::class,
        'total_price' => MoneyCast::class,
        'untaxed_amount' => MoneyCast::class,
        'tax_amount' => MoneyCast::class,
    ];

    protected static function newFactory()
    {
        return InvoiceItemFactory::new();
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function taxes(): MorphToMany
    {
        return $this->morphToMany(Tax::class, 'taxable');
    }
}

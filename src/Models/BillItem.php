<?php

namespace Xoshbin\JmeryarAccounting\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Xoshbin\JmeryarAccounting\Casts\MoneyCast;
use Xoshbin\JmeryarAccounting\Observers\BillItemObserver;

#[ObservedBy([BillItemObserver::class])]
class BillItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_id',
        'product_id',
        'quantity',
        'cost_price',
        'unit_price',
        'total_cost',
        'untaxed_amount',
        'tax_amount',
    ];

    protected $casts = [
        'cost_price' => MoneyCast::class,
        'unit_price' => MoneyCast::class,
        'total_cost' => MoneyCast::class,
        'untaxed_amount' => MoneyCast::class,
        'tax_amount' => MoneyCast::class,
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryBatches()
    {
        return $this->hasMany(InventoryBatch::class);
    }

    public function taxes(): MorphToMany
    {
        return $this->morphToMany(Tax::class, 'taxable');
    }
}

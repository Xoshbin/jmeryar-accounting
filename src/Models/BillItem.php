<?php

namespace Xoshbin\JmeryarAccounting\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Xoshbin\JmeryarAccounting\Casts\MoneyCast;
use Xoshbin\JmeryarAccounting\Database\Factories\BillItemFactory;
use Xoshbin\JmeryarAccounting\Observers\BillItemObserver;

#[ObservedBy([BillItemObserver::class])]
/**
 * @property int $bill_id
 * @property int $product_id
 * @property int $quantity
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $cost_price
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $unit_price
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $total_cost
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $untaxed_amount
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $tax_amount
 */
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

    protected static function newFactory()
    {
        return new BillItemFactory;
    }

    /**
     * @return BelongsTo<Bill, $this>
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<InventoryBatch, $this>
     */
    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class);
    }

    /**
     * @return MorphToMany<Tax, $this>
     */
    public function taxes(): MorphToMany
    {
        return $this->morphToMany(Tax::class, 'taxable');
    }
}

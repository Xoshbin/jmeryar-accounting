<?php

namespace Xoshbin\JmeryarAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Xoshbin\JmeryarAccounting\Casts\MoneyCast;

/**
 * @property int $product_id
 * @property int $bill_item_id
 * @property \Illuminate\Support\Carbon $expiry_date
 * @property int $quantity
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $cost_price
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $unit_price
 */
class InventoryBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'bill_item_id',
        'expiry_date',
        'quantity',
        'cost_price',
        'unit_price',
    ];

    protected $casts = [
        'unit_price' => MoneyCast::class,
        'cost_price' => MoneyCast::class,
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function billItem(): BelongsTo
    {
        return $this->belongsTo(BillItem::class);
    }
}

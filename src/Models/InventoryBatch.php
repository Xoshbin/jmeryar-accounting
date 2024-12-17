<?php

namespace Xoshbin\JmeryarAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Xoshbin\JmeryarAccounting\Casts\MoneyCast;

class InventoryBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'expiry_date',
        'quantity',
        'cost_price',
        'unit_price',
    ];

    protected $casts = [
        'unit_price' => MoneyCast::class,
        'cost_price' => MoneyCast::class,
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

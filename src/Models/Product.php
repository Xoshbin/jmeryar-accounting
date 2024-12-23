<?php

namespace Xoshbin\JmeryarAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Xoshbin\JmeryarAccounting\Casts\MoneyCast;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'sku',
        'description',
        'category_id',
    ];

    protected $casts = [
        'unit_price' => MoneyCast::class,
        'cost_price' => MoneyCast::class,
        'type' => 'string', // 'Product', 'Service'
    ];

    const TYPE_PRODUCT = 'Product';

    const TYPE_SERVICE = 'Service';

    public function inventoryBatches()
    {
        return $this->hasMany(InventoryBatch::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function billItems(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }
}

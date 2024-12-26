<?php

namespace Xoshbin\JmeryarAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Xoshbin\JmeryarAccounting\Casts\MoneyCast;
use Xoshbin\JmeryarAccounting\Database\Factories\ProductFactory;

/**
 * @property string $type
 * @property string $name
 * @property string $sku
 * @property string|null $description
 * @property int $category_id
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $unit_price
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $cost_price
 */
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

    protected static function newFactory()
    {
        return new ProductFactory;
    }

    /**
     * @return HasMany<InventoryBatch, $this>
     */
    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class);
    }

    /**
     * @return BelongsTo<ProductCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * @return HasMany<BillItem, $this>
     */
    public function billItems(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }
}

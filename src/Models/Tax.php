<?php

namespace Xoshbin\JmeryarAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property string $name
 * @property string $type
 * @property string $tax_computation
 * @property float $amount
 * @property int $parent_id
 * @property string $tax_scope
 * @property string $status
 */
class Tax extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'tax_computation',
        'amount',
        'parent_id',
        'tax_scope',
        'status',
    ];

    protected $casts = [
        'type' => 'string', // 'Active', 'Inactive'
    ];

    const STATUS_ACTIVE = 'Active';

    const STATUS_INACTIVE = 'Inactive';

    /**
     * @return BelongsTo<Tax, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'parent_id');
    }

    /**
     * @return HasMany<Tax, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Tax::class, 'parent_id');
    }

    /**
     * Get all of the invoices that are assigned this Tax.
     *
     * @return MorphToMany<InvoiceItem, $this>
     */
    public function invoiceItems(): MorphToMany
    {
        return $this->morphedByMany(InvoiceItem::class, 'taxable');
    }

    /**
     * Get all of the bills that are assigned this Tax.
     *
     * @return MorphToMany<BillItem, $this>
     */
    public function billItems(): MorphToMany
    {
        return $this->morphedByMany(BillItem::class, 'taxable');
    }
}

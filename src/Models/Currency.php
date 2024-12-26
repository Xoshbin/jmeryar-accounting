<?php

namespace Xoshbin\JmeryarAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $code
 * @property string $name
 * @property string $symbol
 * @property string $currency_unit
 * @property string $currency_subunit
 * @property string $status
 */
class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'currency_unit',
        'currency_subunit',
        'status',
    ];

    protected $casts = [
        'status' => 'string', // 'Active', 'Inactive'
    ];

    const TYPE_PRODUCT = 'Active';

    const TYPE_SERVICE = 'Inactive';

    /**
     * @return HasMany<ExchangeRate, $this>
     */
    public function exchangeRatesAsBase(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'base_currency_id');
    }

    /**
     * @return HasMany<ExchangeRate, $this>
     */
    public function exchangeRatesAsTarget(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'target_currency_id');
    }

    /**
     * @return HasMany<Setting, $this>
     */
    public function setting(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    /**
     * @return HasMany<Bill, $this>
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}

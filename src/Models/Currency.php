<?php

namespace Xoshbin\JmeryarAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'currency_unit',
        'currency_subunit',
        'status'
    ];

    protected $casts = [
        'status' => 'string', // 'Active', 'Inactive'
    ];

    const TYPE_PRODUCT = 'Active';
    const TYPE_SERVICE = 'Inactive';

    public function exchangeRatesAsBase() : HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'base_currency_id');
    }

    public function exchangeRatesAsTarget(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'target_currency_id');
    }

    public function setting(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}

<?php

namespace Xoshbin\JmeryarAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Xoshbin\JmeryarAccounting\Database\Factories\ExchangeRateFactory;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'base_currency_id',
        'target_currency_id',
        'rate',
    ];

    protected static function newFactory()
    {
        return ExchangeRateFactory::new();
    }

    public function baseCurrency(): BelongsTo{
        return $this->belongsTo(Currency::class, 'base_currency_id');
    }

    public function targetCurrency(): BelongsTo{
        return $this->belongsTo(Currency::class, 'target_currency_id');
    }
}

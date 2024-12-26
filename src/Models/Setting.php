<?php

namespace Xoshbin\JmeryarAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $company_name
 * @property string $company_email
 * @property string $company_phone
 * @property string $company_address
 * @property string $company_website
 * @property string $company_logo
 * @property int $currency_id
 */
class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'company_email',
        'company_phone',
        'company_address',
        'company_website',
        'company_logo',
        'currency_id',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}

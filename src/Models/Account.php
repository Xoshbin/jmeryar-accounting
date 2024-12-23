<?php

namespace Xoshbin\JmeryarAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'code',
        'parent_id',
    ];

    protected $casts = [
        'type' => 'string',
    ];

    public const TYPE_ASSET = 'Asset';

    public const TYPE_LIABILITY = 'Liability';

    public const TYPE_EQUITY = 'Equity';

    public const TYPE_REVENUE = 'Revenue';

    public const TYPE_EXPENSE = 'Expense';

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }
}

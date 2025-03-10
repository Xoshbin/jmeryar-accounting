<?php

namespace Xoshbin\JmeryarAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $name
 * @property string $type
 * @property string $code
 * @property int $parent_id
 */
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

    /**
     * @return BelongsTo<Account, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /**
     * @return HasMany<Account, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    /**
     * @return HasMany<JournalEntry, $this>
     */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }
}

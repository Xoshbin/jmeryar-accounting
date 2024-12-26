<?php

namespace Xoshbin\JmeryarAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Xoshbin\JmeryarAccounting\Casts\MoneyCast;

/**
 * @property \Illuminate\Support\Carbon $date
 * @property string|null $note
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $amount
 * @property string $transaction_type
 */
class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'note',
        'amount',
        'transaction_type',
    ];

    protected $casts = [
        'amount' => MoneyCast::class,
        'transaction_type' => 'string',
    ];

    public const TYPE_DEBIT = 'Debit';

    public const TYPE_CREDIT = 'Credit';

    /**
     * Get all of the payments that are assigned this Transaction.
     * @return MorphToMany<Payment, $this>
     */
    public function payments(): MorphToMany
    {
        return $this->morphedByMany(Payment::class, 'transactionable');
    }

    /**
     * @return MorphToMany<JournalEntry, $this>
     */
    public function journalEntries(): MorphToMany
    {
        return $this->morphToMany(JournalEntry::class, 'j_entryable', 'j_entryables');
    }
}

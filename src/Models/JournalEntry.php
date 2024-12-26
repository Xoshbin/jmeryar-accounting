<?php

namespace Xoshbin\JmeryarAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Xoshbin\JmeryarAccounting\Casts\MoneyCast;

/**
 * @property int $transaction_id
 * @property int $account_id
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $debit
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $credit
 * @property string $label
 */
class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'account_id',
        'debit',
        'credit',
        'label',
    ];

    protected $casts = [
        'debit' => MoneyCast::class,
        'credit' => MoneyCast::class,
    ];

    /**
     * @return MorphToMany<Bill, $this>
     */
    public function bills(): MorphToMany
    {
        return $this->morphedByMany(Bill::class, 'j_entryable', 'j_entryables', 'journal_entry_id', 'j_entryable_id');
    }

    /**
     * @return MorphToMany<Invoice, $this>
     */
    public function invoices(): MorphToMany
    {
        return $this->morphedByMany(Invoice::class, 'j_entryable', 'j_entryables', 'journal_entry_id', 'j_entryable_id');
    }

    /**
     * @return MorphToMany<Payment, $this>
     */
    public function payments(): MorphToMany
    {
        return $this->morphedByMany(Payment::class, 'j_entryable', 'j_entryables', 'journal_entry_id', 'j_entryable_id');
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return MorphToMany<Bill|Invoice, $this>
     */
    public function related(): MorphToMany
    {
        return $this->morphedByMany(Bill::class, 'j_entryables')
            ->union($this->morphedByMany(Invoice::class, 'j_entryables'));
    }
}

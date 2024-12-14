<?php

namespace Xoshbin\JmeryarAccounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Xoshbin\JmeryarAccounting\Casts\MoneyCast;

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

    public function bills(): MorphToMany
    {
        return $this->morphedByMany(Bill::class, 'j_entryable', 'j_entryables', 'journal_entry_id', 'j_entryable_id');
    }

    public function invoices(): MorphToMany
    {
        return $this->morphedByMany(Invoice::class, 'j_entryable', 'j_entryables', 'journal_entry_id', 'j_entryable_id');
    }


    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    // Generic method to retrieve the related models
    public function related(): MorphToMany
    {
        return $this->morphedByMany(Bill::class, 'j_entryables')
            ->union($this->morphedByMany(Invoice::class, 'j_entryables'));
    }
}

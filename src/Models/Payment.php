<?php

namespace Xoshbin\JmeryarAccounting\Models;

use Xoshbin\JmeryarAccounting\Database\Factories\PaymentFactory;
use Xoshbin\JmeryarAccounting\Observers\PaymentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Xoshbin\JmeryarAccounting\Casts\MoneyCast;

#[ObservedBy([PaymentObserver::class])]
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'payment_date',
        'payment_type',
        'payment_method',
        'note',
        'currency_id',
        'exchange_rate',
        'amount_in_invoice_currency',
    ];

    protected $casts = [
        'amount' => MoneyCast::class,
        'amount_in_invoice_currency' => MoneyCast::class,
        'payment_type' => 'string', //'Income', 'Expense'
    ];

    public const TYPE_INCOME = 'Income';
    public const TYPE_EXPENSE = 'Expense';

    /**
     * Define the polymorphic relationship to the parent model (e.g., Invoice, Bill).
     */
    public function paymentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get all of the transactions for the Payment.
     */
    public function transactions(): MorphToMany
    {
        return $this->morphToMany(Transaction::class, 'transactionable');
    }

    public function journalEntries(): MorphToMany
    {
        return $this->morphToMany(JournalEntry::class, 'j_entryable', 'j_entryables');
    }
}

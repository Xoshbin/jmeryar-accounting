<?php

namespace Xoshbin\JmeryarAccounting\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Xoshbin\JmeryarAccounting\Casts\MoneyCast;
use Xoshbin\JmeryarAccounting\Database\Factories\PaymentFactory;
use Xoshbin\JmeryarAccounting\Observers\PaymentObserver;

#[ObservedBy([PaymentObserver::class])]
/**
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $amount
 * @property string $payment_date
 * @property string $payment_type
 * @property string $payment_method
 * @property string|null $note
 * @property int $currency_id
 * @property float $exchange_rate
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $amount_in_invoice_currency
 */
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

    protected static function newFactory()
    {
        return new PaymentFactory;
    }

    /**
     * Define the polymorphic relationship to the parent model (e.g., Invoice, Bill).
     * @return MorphTo<Invoice|Bill, $this>
     */
    public function paymentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get all of the transactions for the Payment.
     * @return MorphToMany<Transaction, $this>
     */
    public function transactions(): MorphToMany
    {
        return $this->morphToMany(Transaction::class, 'transactionable');
    }

    /**
     * @return MorphToMany<JournalEntry, $this>
     */
    public function journalEntries(): MorphToMany
    {
        return $this->morphToMany(JournalEntry::class, 'j_entryable', 'j_entryables');
    }
}

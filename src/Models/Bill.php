<?php

namespace Xoshbin\JmeryarAccounting\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Xoshbin\JmeryarAccounting\Casts\MoneyCast;
use Xoshbin\JmeryarAccounting\Database\Factories\BillFactory;
use Xoshbin\JmeryarAccounting\Observers\BillObserver;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy([BillObserver::class])]
/**
 * @property string $bill_number
 * @property \Illuminate\Support\Carbon $bill_date
 * @property \Illuminate\Support\Carbon $due_date
 * @property int $supplier_id
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $total_amount
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $total_paid_amount
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $amount_due
 * @property string $status
 * @property string $note
 * @property int $expense_account_id
 * @property int $liability_account_id
 * @property int $currency_id
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $untaxed_amount
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $tax_amount
 */
class Bill extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'bill_number',
        'bill_date',
        'due_date',
        'supplier_id',
        'total_amount',
        'total_paid_amount',
        'amount_due',
        'status',
        'note',
        'expense_account_id',
        'liability_account_id',
        'currency_id',
        'untaxed_amount',
        'tax_amount',
    ];

    protected $casts = [
        'total_amount' => MoneyCast::class,
        'total_paid_amount' => MoneyCast::class,
        'amount_due' => MoneyCast::class,
        'untaxed_amount' => MoneyCast::class,
        'tax_amount' => MoneyCast::class,
        'status' => 'string', //'Draft', 'Received', 'Partial', 'Paid'
    ];

    public const TYPE_DRAFT = 'Draft';

    public const TYPE_RECEIVED = 'Received';

    public const TYPE_PARTIAL = 'Partial';

    public const TYPE_PAID = 'Paid';

    protected static function newFactory()
    {
        return new BillFactory;
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return MorphToMany<JournalEntry, $this>
     */
    public function journalEntries(): MorphToMany
    {
        return $this->morphToMany(JournalEntry::class, 'j_entryable', 'j_entryables');
    }

    /**
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * @return HasMany<BillItem, $this>
     */
    public function billItems(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    /**
     * Get all of the payments for the bill.
     *
     * @return MorphMany<Payment, $this>
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    /**
     * @return MorphToMany<Tax, $this>
     */
    public function taxes(): MorphToMany
    {
        return $this->morphToMany(Tax::class, 'taxable');
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function liabilityAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'liability_account_id');
    }
}

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
use Xoshbin\JmeryarAccounting\Database\Factories\InvoiceFactory;
use Xoshbin\JmeryarAccounting\Observers\InvoiceObserver;

#[ObservedBy([InvoiceObserver::class])]
/**
 * @property string $invoice_number
 * @property \Illuminate\Support\Carbon $invoice_date
 * @property \Illuminate\Support\Carbon $due_date
 * @property int $customer_id
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $total_amount
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $total_paid_amount
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $amount_due
 * @property string $status
 * @property string $note
 * @property int $revenue_account_id
 * @property int $asset_account_id
 * @property int $currency_id
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $untaxed_amount
 * @property \Xoshbin\JmeryarAccounting\Casts\MoneyCast $tax_amount
 */
class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'invoice_date',
        'due_date',
        'customer_id',
        'total_amount',
        'total_paid_amount',
        'amount_due',
        'status',
        'note',
        'revenue_account_id',
        'asset_account_id',
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
        'status' => 'string', // 'Draft', 'Sent', 'Partial', 'Paid'
    ];

    public const TYPE_DRAFT = 'Draft';

    public const TYPE_SENT = 'Sent';

    public const TYPE_PARTIAL = 'Partial';

    public const TYPE_PAID = 'Paid';

    protected static function newFactory()
    {
        return new InvoiceFactory;
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
     * @return HasMany<InvoiceItem, $this>
     */
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get all of the payments for the invoice.
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
     * Relationship to the revenue account
     *
     * @return BelongsTo<Account, $this>
     */
    public function revenueAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'revenue_account_id');
    }

    /**
     * Relationship to the inventory/COGS account
     *
     * @return BelongsTo<Account, $this>
     */
    public function inventoryAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }
}

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
use Xoshbin\JmeryarAccounting\Observers\InvoiceObserver;

#[ObservedBy([InvoiceObserver::class])]
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
        'inventory_account_id',
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
        'status' => 'string', //'Draft', 'Sent', 'Partial', 'Paid'
    ];

    public const TYPE_DRAFT = 'Draft';

    public const TYPE_SENT = 'Sent';

    public const TYPE_PARTIAL = 'Partial';

    public const TYPE_PAID = 'Paid';

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function journalEntries(): MorphToMany
    {
        return $this->morphToMany(JournalEntry::class, 'j_entryable', 'j_entryables');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get all of the payments for the invoice.
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function taxes(): MorphToMany
    {
        return $this->morphToMany(Tax::class, 'taxable');
    }

    // Relationship to the revenue account
    public function revenueAccount()
    {
        return $this->belongsTo(Account::class, 'revenue_account_id');
    }

    // Relationship to the inventory/COGS account
    public function inventoryAccount()
    {
        return $this->belongsTo(Account::class, 'inventory_account_id');
    }
}

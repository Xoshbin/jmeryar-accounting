<?php

namespace Xoshbin\JmeryarAccounting\Models;

use Xoshbin\JmeryarAccounting\Database\Factories\BillFactory;
use Xoshbin\JmeryarAccounting\Observers\BillObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Xoshbin\JmeryarAccounting\Casts\MoneyCast;

#[ObservedBy([BillObserver::class])]
class Bill extends Model
{
    use HasFactory;

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
        'tax_amount'
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function journalEntries(): MorphToMany
    {
        return $this->morphToMany(JournalEntry::class, 'j_entryable', 'j_entryables');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function billItems(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    /**
     * Get all of the payments for the bill.
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function taxes(): MorphToMany
    {
        return $this->morphToMany(Tax::class, 'taxable');
    }

    // Relationship to the expense account
    public function expenseAccount()
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    // Relationship to the liability account (e.g., Accounts Payable)
    public function liabilityAccount()
    {
        return $this->belongsTo(Account::class, 'liability_account_id');
    }
}

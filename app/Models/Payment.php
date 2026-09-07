<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    public const METHOD_CASH = 'CASH';

    public const METHOD_BANK_TRANSFER = 'BANK_TRANSFER';

    public const METHOD_QRIS = 'QRIS';

    public const METHOD_OTHER = 'OTHER';

    protected $fillable = [
        'payment_number',
        'loan_id',
        'customer_id',
        'installment_id',
        'payment_date',
        'amount',
        'principal_component',
        'interest_component',
        'penalty_component',
        'payment_method',
        'reference_number',
        'received_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'integer',
            'principal_component' => 'integer',
            'interest_component' => 'integer',
            'penalty_component' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Loan, $this>
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Installment, $this>
     */
    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * @return HasOne<PaymentReversal, $this>
     */
    public function reversal(): HasOne
    {
        return $this->hasOne(PaymentReversal::class);
    }

    public function isReversed(): bool
    {
        return $this->relationLoaded('reversal')
            ? $this->reversal !== null
            : $this->reversal()->exists();
    }
}

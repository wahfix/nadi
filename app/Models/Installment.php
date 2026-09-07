<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Installment extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_PARTIALLY_PAID = 'PARTIALLY_PAID';

    public const STATUS_PAID = 'PAID';

    public const STATUS_OVERDUE = 'OVERDUE';

    public const STATUS_WAIVED = 'WAIVED';

    protected $fillable = [
        'loan_id',
        'installment_number',
        'due_date',
        'principal_due',
        'interest_due',
        'penalty_due',
        'total_due',
        'principal_paid',
        'interest_paid',
        'penalty_paid',
        'total_paid',
        'remaining_amount',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'installment_number' => 'integer',
            'due_date' => 'date',
            'principal_due' => 'integer',
            'interest_due' => 'integer',
            'penalty_due' => 'integer',
            'total_due' => 'integer',
            'principal_paid' => 'integer',
            'interest_paid' => 'integer',
            'penalty_paid' => 'integer',
            'total_paid' => 'integer',
            'remaining_amount' => 'integer',
            'paid_at' => 'datetime',
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
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}

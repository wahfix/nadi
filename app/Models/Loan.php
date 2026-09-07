<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    // 11 Loan Status Constants according to Master Spec
    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_SUBMITTED = 'SUBMITTED';

    public const STATUS_UNDER_REVIEW = 'UNDER_REVIEW';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

    public const STATUS_READY_FOR_DISBURSEMENT = 'READY_FOR_DISBURSEMENT';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_OVERDUE = 'OVERDUE';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_DEFAULTED = 'DEFAULTED';

    public const STATUS_CANCELLED = 'CANCELLED';

    // Interest calculation methods
    public const METHOD_FLAT = 'FLAT';

    public const METHOD_REDUCING_BALANCE = 'REDUCING_BALANCE';

    // Installment frequencies
    public const FREQUENCY_MONTHLY = 'MONTHLY';

    public const FREQUENCY_WEEKLY = 'WEEKLY';

    protected $fillable = [
        'loan_number',
        'customer_id',
        'principal_amount',
        'interest_rate',
        'interest_method',
        'tenor',
        'installment_frequency',
        'disbursement_date',
        'first_due_date',
        'maturity_date',
        'total_interest',
        'total_payable',
        'installment_amount',
        'outstanding_principal',
        'outstanding_interest',
        'outstanding_penalty',
        'outstanding_total',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'disbursed_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'principal_amount' => 'integer',
            'interest_rate' => 'integer',
            'tenor' => 'integer',
            'total_interest' => 'integer',
            'total_payable' => 'integer',
            'installment_amount' => 'integer',
            'outstanding_principal' => 'integer',
            'outstanding_interest' => 'integer',
            'outstanding_penalty' => 'integer',
            'outstanding_total' => 'integer',
            'disbursement_date' => 'date',
            'first_due_date' => 'date',
            'maturity_date' => 'date',
            'approved_at' => 'datetime',
            'disbursed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return HasMany<Installment, $this>
     */
    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<CollectionActivity, $this>
     */
    public function collectionActivities(): HasMany
    {
        return $this->hasMany(CollectionActivity::class);
    }

    /**
     * @return HasMany<Collateral, $this>
     */
    public function collaterals(): HasMany
    {
        return $this->hasMany(Collateral::class);
    }

    /**
     * @return HasMany<LoanStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(LoanStatusHistory::class);
    }

    /**
     * @return HasMany<IdentityVerification, $this>
     */
    public function identityVerifications(): HasMany
    {
        return $this->hasMany(IdentityVerification::class);
    }

    /**
     * @return HasMany<CollateralRelease, $this>
     */
    public function collateralReleases(): HasMany
    {
        return $this->hasMany(CollateralRelease::class);
    }

    /**
     * Check if loan is completely paid off.
     */
    public function isPaidOff(): bool
    {
        return $this->outstanding_total === 0;
    }
}

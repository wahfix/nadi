<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IdentityVerification extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    public $timestamps = false;

    public const METHOD_GOVERNMENT_ID = 'GOVERNMENT_ID';

    public const METHOD_ACCOUNT_MATCH = 'ACCOUNT_MATCH';

    public const METHOD_MANUAL_CHECK = 'MANUAL_CHECK';

    public const METHOD_OTHER = 'OTHER';

    public const RESULT_VERIFIED = 'VERIFIED';

    public const RESULT_FAILED = 'FAILED';

    public const RESULT_REQUIRES_REVIEW = 'REQUIRES_REVIEW';

    protected $fillable = [
        'customer_id',
        'loan_id',
        'release_id',
        'verification_method',
        'verified_name',
        'verified_id_number',
        'result',
        'verifier_id',
        'verification_timestamp',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'verification_timestamp' => 'datetime',
            'created_at' => 'datetime',
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
     * @return BelongsTo<Loan, $this>
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifier_id');
    }

    /**
     * @return HasOne<CollateralRelease, $this>
     */
    public function collateralRelease(): HasOne
    {
        return $this->hasOne(CollateralRelease::class, 'verified_identity_id');
    }
}

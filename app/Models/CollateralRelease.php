<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollateralRelease extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'release_number',
        'collateral_id',
        'loan_id',
        'customer_id',
        'verified_identity_id',
        'released_to_name',
        'relationship_to_customer',
        'release_date',
        'release_location',
        'released_by',
        'witness_id',
        'customer_signature_reference',
        'handover_notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'release_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Collateral, $this>
     */
    public function collateral(): BelongsTo
    {
        return $this->belongsTo(Collateral::class);
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
     * @return BelongsTo<IdentityVerification, $this>
     */
    public function identityVerification(): BelongsTo
    {
        return $this->belongsTo(IdentityVerification::class, 'verified_identity_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function witness(): BelongsTo
    {
        return $this->belongsTo(User::class, 'witness_id');
    }
}

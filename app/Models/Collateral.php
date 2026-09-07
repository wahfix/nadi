<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Collateral extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    public const TYPE_DOCUMENT = 'DOCUMENT';

    public const TYPE_VEHICLE = 'VEHICLE';

    public const TYPE_ELECTRONIC = 'ELECTRONIC';

    public const TYPE_OTHER = 'OTHER';

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_RECEIVED = 'RECEIVED';

    public const STATUS_IN_CUSTODY = 'IN_CUSTODY';

    public const STATUS_READY_FOR_RELEASE = 'READY_FOR_RELEASE';

    public const STATUS_RELEASED = 'RELEASED';

    public const STATUS_DISPUTED = 'DISPUTED';

    protected $fillable = [
        'collateral_code',
        'loan_id',
        'customer_id',
        'collateral_type',
        'description',
        'identification_number',
        'estimated_value',
        'received_date',
        'condition_on_receipt',
        'storage_location',
        'custody_status',
        'received_by',
        'released_by',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'estimated_value' => 'integer',
            'received_date' => 'date',
            'released_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    /**
     * @return HasOne<CollateralRelease, $this>
     */
    public function release(): HasOne
    {
        return $this->hasOne(CollateralRelease::class);
    }
}

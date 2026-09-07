<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employment extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    public const TYPE_PERMANENT = 'PERMANENT';

    public const TYPE_CONTRACT = 'CONTRACT';

    public const TYPE_SELF_EMPLOYED = 'SELF_EMPLOYED';

    public const TYPE_OTHER = 'OTHER';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_RESIGNED = 'RESIGNED';

    public const STATUS_TERMINATED = 'TERMINATED';

    public const STATUS_UNKNOWN = 'UNKNOWN';

    protected $fillable = [
        'customer_id',
        'company_name',
        'department',
        'position',
        'employment_type',
        'employment_start_date',
        'estimated_monthly_income',
        'employment_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'employment_start_date' => 'date',
            'estimated_monthly_income' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}

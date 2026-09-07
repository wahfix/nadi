<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_INACTIVE = 'INACTIVE';

    public const STATUS_BLOCKED = 'BLOCKED';

    public const GENDER_MALE = 'MALE';

    public const GENDER_FEMALE = 'FEMALE';

    /**
     * Nomor identitas dengan sebagian digit disamarkan untuk tampilan umum.
     */
    public function getNikMaskedAttribute(): string
    {
        $nik = $this->national_id_number;

        if ($nik === '') {
            return '-';
        }

        if (strlen($nik) <= 8) {
            return '***'.substr($nik, -2);
        }

        return substr($nik, 0, 6).str_repeat('*', strlen($nik) - 10).substr($nik, -4);
    }

    protected $fillable = [
        'customer_code',
        'full_name',
        'national_id_number',
        'date_of_birth',
        'gender',
        'phone',
        'email',
        'address',
        'city',
        'emergency_contact_name',
        'emergency_contact_phone',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    /**
     * @return HasMany<Employment, $this>
     */
    public function employments(): HasMany
    {
        return $this->hasMany(Employment::class);
    }

    /**
     * @return HasOne<Employment, $this>
     */
    public function activeEmployment(): HasOne
    {
        return $this->hasOne(Employment::class)->latestOfMany();
    }

    /**
     * @return HasMany<Loan, $this>
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * @return HasMany<Collateral, $this>
     */
    public function collaterals(): HasMany
    {
        return $this->hasMany(Collateral::class);
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
}

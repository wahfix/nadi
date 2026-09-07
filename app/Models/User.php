<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * The roles that belong to the user.
     *
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    /**
     * Check if the user has a given role.
     *
     * @param  string|array<string>  $roles
     */
    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return $this->hasAnyRole($roles);
        }

        return $this->roles->contains('name', $roles);
    }

    /**
     * Check if user has any of the given roles.
     *
     * @param  array<string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->whereIn('name', $roles)->isNotEmpty();
    }

    /**
     * Check if user has all of the given roles.
     *
     * @param  array<string>  $roles
     */
    public function hasAllRoles(array $roles): bool
    {
        return count($roles) === $this->roles->whereIn('name', $roles)->count();
    }

    /**
     * Check if the user has a given permission.
     */
    public function hasPermission(string $permission): bool
    {
        // Admin has all permissions
        if ($this->hasRole(Role::ADMIN)) {
            return true;
        }

        return $this->allPermissions()->contains('name', $permission);
    }

    /**
     * Get all unique permissions for the user through roles.
     *
     * @return Collection<int, Permission>
     */
    public function allPermissions(): Collection
    {
        return $this->roles
            ->loadMissing('permissions')
            ->flatMap(fn (Role $role): Collection => $role->permissions)
            ->unique('id');
    }

    /**
     * Primary role display name.
     */
    public function primaryRoleName(): string
    {
        $role = $this->roles->first();

        return $role ? $role->display_name : 'Pengguna';
    }

    // --- Relationships according to Master Build Spec ---

    /**
     * @return HasMany<Loan, $this>
     */
    public function createdLoans(): HasMany
    {
        return $this->hasMany(Loan::class, 'created_by');
    }

    /**
     * @return HasMany<Loan, $this>
     */
    public function approvedLoans(): HasMany
    {
        return $this->hasMany(Loan::class, 'approved_by');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function paymentsReceived(): HasMany
    {
        return $this->hasMany(Payment::class, 'received_by');
    }

    /**
     * @return HasMany<PaymentReversal, $this>
     */
    public function paymentsReversed(): HasMany
    {
        return $this->hasMany(PaymentReversal::class, 'reversed_by');
    }

    /**
     * @return HasMany<CollectionActivity, $this>
     */
    public function collectionActivities(): HasMany
    {
        return $this->hasMany(CollectionActivity::class, 'collector_id');
    }

    /**
     * @return HasMany<Collateral, $this>
     */
    public function collateralsReceived(): HasMany
    {
        return $this->hasMany(Collateral::class, 'received_by');
    }

    /**
     * @return HasMany<Collateral, $this>
     */
    public function collateralsReleased(): HasMany
    {
        return $this->hasMany(Collateral::class, 'released_by');
    }

    /**
     * @return HasMany<IdentityVerification, $this>
     */
    public function identityVerifications(): HasMany
    {
        return $this->hasMany(IdentityVerification::class, 'verifier_id');
    }

    /**
     * @return HasMany<CollateralRelease, $this>
     */
    public function collateralReleases(): HasMany
    {
        return $this->hasMany(CollateralRelease::class, 'released_by');
    }

    /**
     * @return HasMany<CollateralRelease, $this>
     */
    public function witnessedReleases(): HasMany
    {
        return $this->hasMany(CollateralRelease::class, 'witness_id');
    }

    /**
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }
}

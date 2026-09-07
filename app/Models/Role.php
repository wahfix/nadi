<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    /** @use HasFactory<Factory> */
    use HasFactory;

    public const ADMIN = 'ADMIN';

    public const LO = 'LO';

    public const LC = 'LC';

    public const CASHIER = 'CASHIER';

    public const COLLATERAL_OFFICER = 'COLLATERAL_OFFICER';

    public const IDENTITY_VERIFIER = 'IDENTITY_VERIFIER';

    public const AUDITOR = 'AUDITOR';

    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    /**
     * The users that belong to the role.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')->withTimestamps();
    }

    /**
     * The permissions that belong to the role.
     *
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role')->withTimestamps();
    }
}

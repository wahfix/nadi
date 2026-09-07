<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\Role;
use App\Models\User;
use App\Policies\CustomerPolicy;
use App\Policies\LoanPolicy;
use App\Policies\UserPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
    }

    /**
     * Configure RBAC gates and Blade directives.
     */
    protected function configureAuthorization(): void
    {
        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole(Role::ADMIN)) {
                return true;
            }

            if ($user->hasPermission($ability)) {
                return true;
            }

            return null;
        });

        Gate::policy(User::class, UserPolicy::class);

        Gate::policy(Customer::class, CustomerPolicy::class);

        Gate::policy(Loan::class, LoanPolicy::class);

        Blade::if('role', function ($role) {
            return auth()->check() && auth()->user()->hasRole($role);
        });

        Blade::if('permission', function ($permission) {
            return auth()->check() && auth()->user()->hasPermission($permission);
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

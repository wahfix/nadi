<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * Display a paginated list of system users.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        $users = User::with('roles')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('modules.users.index', [
            'users' => $users,
        ]);
    }

    /**
     * Show the form to create a new system user.
     */
    public function create(): View
    {
        Gate::authorize('create', User::class);

        $roles = Role::orderBy('display_name')->get();

        return view('modules.users.create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Persist a newly created system user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = $this->userService->createUser([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role_id' => (int) $validated['role_id'],
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', "Pengguna {$user->name} berhasil ditambahkan.");
    }
}

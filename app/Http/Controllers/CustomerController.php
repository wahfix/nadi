<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {}

    /**
     * Display a paginated, searchable, filterable list of customers.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Customer::class);

        $customers = Customer::query()
            ->with('activeEmployment')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($inner) use ($search) {
                    $inner->where('full_name', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('national_id_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('city'), fn ($query) => $query->where('city', $request->string('city')->toString()))
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('modules.customers.index', [
            'customers' => $customers,
            'statusOptions' => [
                Customer::STATUS_ACTIVE,
                Customer::STATUS_INACTIVE,
                Customer::STATUS_BLOCKED,
            ],
            'cities' => Customer::query()
                ->select('city')
                ->distinct()
                ->orderBy('city')
                ->pluck('city'),
        ]);
    }

    /**
     * Show the form to register a new customer.
     */
    public function create(): View
    {
        Gate::authorize('create', Customer::class);

        return view('modules.customers.create');
    }

    /**
     * Persist a newly registered customer.
     */
    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = $this->customerService->createCustomer($request->validated());

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', "Nasabah {$customer->full_name} berhasil didaftarkan.");
    }

    /**
     * Display the customer detail page with its related records.
     */
    public function show(Customer $customer): View
    {
        Gate::authorize('view', $customer);

        $customer->load(['employments', 'loans', 'payments', 'collaterals', 'collectionActivities', 'identityVerifications']);

        $auditLogs = AuditLog::query()
            ->where('entity_type', Customer::class)
            ->where('entity_id', $customer->id)
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('modules.customers.show', [
            'customer' => $customer,
            'auditLogs' => $auditLogs,
        ]);
    }

    /**
     * Show the form to edit an existing customer profile.
     */
    public function edit(Customer $customer): View
    {
        Gate::authorize('update', $customer);

        return view('modules.customers.edit', [
            'customer' => $customer,
        ]);
    }

    /**
     * Update an existing customer profile.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->customerService->updateCustomer($customer, $request->validated());

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Data nasabah berhasil diperbarui.');
    }
}

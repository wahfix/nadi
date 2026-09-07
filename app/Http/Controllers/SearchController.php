<?php

namespace App\Http\Controllers;

use App\Http\Requests\GlobalSearchRequest;
use App\Models\Collateral;
use App\Models\CollateralRelease;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Display the global search form.
     */
    public function index(): View
    {
        $type = (string) request()->query('type', 'customer');
        $query = (string) request()->query('q', '');

        return view('modules.search.index', [
            'typeOptions' => $this->typeOptions(),
            'selectedType' => $type,
            'query' => $query,
            'results' => null,
        ]);
    }

    /**
     * Execute the global search and render the results page.
     */
    public function results(GlobalSearchRequest $request): View
    {
        $type = $request->validated('type');
        $query = trim($request->validated('q'));

        Gate::authorize('viewAny', $this->modelClass($type));

        if ($query === '') {
            return $this->index();
        }

        $results = $this->search($type, $query);

        return view('modules.search.index', [
            'typeOptions' => $this->typeOptions(),
            'selectedType' => $type,
            'query' => $query,
            'results' => $results,
        ]);
    }

    /**
     * Search a record across the selected entity type.
     *
     * @return LengthAwarePaginator<int, mixed>
     */
    private function search(string $type, string $query): LengthAwarePaginator
    {
        $term = "%{$query}%";

        return match ($type) {
            'customer' => Customer::query()
                ->where(function ($builder) use ($term) {
                    $builder->where('customer_code', 'like', $term)
                        ->orWhere('full_name', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('national_id_number', 'like', $term);
                })
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString(),
            'loan' => Loan::query()
                ->with(['customer'])
                ->where('loan_number', 'like', $term)
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString(),
            'payment' => Payment::query()
                ->with(['loan.customer'])
                ->where('payment_number', 'like', $term)
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString(),
            'collateral' => Collateral::query()
                ->with(['loan.customer'])
                ->where('collateral_code', 'like', $term)
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString(),
            'release' => CollateralRelease::query()
                ->with(['customer', 'collateral'])
                ->where('release_number', 'like', $term)
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString(),
            default => abort(400, 'Jenis pencarian tidak dikenal.'),
        };
    }

    /**
     * Map a search type to its model class for policy gating.
     *
     * @return class-string
     */
    private function modelClass(string $type): string
    {
        return match ($type) {
            'customer' => Customer::class,
            'loan' => Loan::class,
            'payment' => Payment::class,
            'collateral' => Collateral::class,
            'release' => CollateralRelease::class,
            default => abort(400, 'Jenis pencarian tidak dikenal.'),
        };
    }

    /**
     * @return array<string, string>
     */
    private function typeOptions(): array
    {
        return [
            'customer' => 'Nasabah (No. CUS / Nama / Telepon / NIK)',
            'loan' => 'Pinjaman (NADI-LOAN-...)',
            'payment' => 'Pembayaran (PAY-...)',
            'collateral' => 'Jaminan (COL-...)',
            'release' => 'Pelepasan (REL-...)',
        ];
    }
}

<div class="flex flex-col gap-6">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card icon="users" label="Total Nasabah" :value="number_format($dashboard['total_customers'])" />
        <x-stat-card icon="clipboard-document-list" label="Pinjaman Anda" :value="$dashboard['total_loans']" accent="blue" />
        <x-stat-card icon="check-badge" label="Disetujui" :value="$dashboard['pipeline'][\App\Models\Loan::STATUS_APPROVED]" accent="green" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        @foreach ([\App\Models\Loan::STATUS_DRAFT => ['Menunggu (Draft)', 'neutral'], \App\Models\Loan::STATUS_SUBMITTED => ['Diajukan', 'blue'], \App\Models\Loan::STATUS_UNDER_REVIEW => ['Sedang Direview', 'amber'], \App\Models\Loan::STATUS_APPROVED => ['Disetujui', 'green']] as $status => [$label, $accent])
            <x-stat-card icon="inbox-stack" :label="$label" :value="$dashboard['pipeline'][$status]" :accent="$accent" />
        @endforeach
    </div>

    <div class="flex flex-wrap gap-3">
        @can('customers.create')
            <flux:button as="a" :href="route('customers.create')" wire:navigate icon="user-plus">
                Daftarkan Nasabah Baru
            </flux:button>
        @endcan
        @can('loans.create')
            <flux:button as="a" :href="route('loans.create')" wire:navigate variant="primary" icon="plus">
                Buat Pengajuan Pinjaman
            </flux:button>
        @endcan
    </div>
</div>
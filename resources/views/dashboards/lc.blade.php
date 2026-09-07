<div class="flex flex-col gap-6">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card icon="users" label="Nasabah dalam Penanganan" :value="$dashboard['assigned_borrowers']" accent="blue" />
        <x-stat-card icon="banknotes" label="Sisa Tagihan Berjalan" :value="format_rupiah($dashboard['total_outstanding'])" accent="amber" />
        <x-stat-card icon="exclamation-triangle" label="Total Tunggakan" :value="format_rupiah($dashboard['overdue_total'])" accent="red" />
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <flux:card>
            <flux:heading size="lg">Jatuh Tempo Hari Ini</flux:heading>
            @forelse ($dashboard['due_today'] as $installment)
                <div class="flex items-center justify-between gap-3 border-b border-neutral-100 py-2.5 last:border-0 dark:border-neutral-800">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ $installment->loan->customer?->full_name }}</p>
                        <p class="font-mono text-xs text-neutral-500">{{ $installment->loan->loan_number }} • Angsuran ke-{{ $installment->installment_number }}</p>
                    </div>
                    <p class="shrink-0 font-semibold text-red-600">{{ format_rupiah($installment->remaining_amount) }}</p>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-neutral-500">Tidak ada jatuh tempo hari ini.</p>
            @endforelse
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Janji Bayar Mendatang</flux:heading>
            @forelse ($dashboard['promises'] as $promise)
                <div class="flex items-center justify-between gap-3 border-b border-neutral-100 py-2.5 last:border-0 dark:border-neutral-800">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ $promise->loan->customer?->full_name }}</p>
                        <p class="font-mono text-xs text-neutral-500">{{ $promise->loan->loan_number }} • {{ format_date($promise->promise_to_pay_date) }}</p>
                    </div>
                    <p class="shrink-0 font-semibold">{{ format_rupiah($promise->promise_to_pay_amount) }}</p>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-neutral-500">Belum ada janji bayar.</p>
            @endforelse
        </flux:card>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <flux:card>
            <flux:heading size="lg">Jatuh Tempo Minggu Ini</flux:heading>
            @forelse ($dashboard['due_this_week'] as $installment)
                <div class="flex items-center justify-between gap-3 border-b border-neutral-100 py-2.5 last:border-0 dark:border-neutral-800">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ $installment->loan->customer?->full_name }}</p>
                        <p class="font-mono text-xs text-neutral-500">{{ $installment->loan->loan_number }} • {{ format_date($installment->due_date) }}</p>
                    </div>
                    <p class="shrink-0 font-semibold text-amber-600">{{ format_rupiah($installment->remaining_amount) }}</p>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-neutral-500">Tidak ada jatuh tempo minggu ini.</p>
            @endforelse
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Tunggakan (Menunggak)</flux:heading>
            <flux:subheading>Maksimal 10 angsuran menunggak terbaru.</flux:subheading>
            @forelse ($dashboard['overdue'] as $installment)
                <div class="flex items-center justify-between gap-3 border-b border-neutral-100 py-2.5 last:border-0 dark:border-neutral-800">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ $installment->loan->customer?->full_name }}</p>
                        <p class="font-mono text-xs text-neutral-500">{{ $installment->loan->loan_number }} • Jatuh tempo {{ format_date($installment->due_date) }}</p>
                    </div>
                    <p class="shrink-0 font-semibold text-red-600">{{ format_rupiah($installment->remaining_amount) }}</p>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-neutral-500">Tidak ada tunggakan.</p>
            @endforelse
        </flux:card>
    </div>
</div>
<div class="flex flex-col gap-6">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-stat-card icon="users" label="Total Nasabah" :value="number_format($dashboard['portfolio']['total_customers'])" />
        <x-stat-card icon="clipboard-document-list" label="Pinjaman Aktif" :value="$dashboard['portfolio']['active_loans']" accent="blue" />
        <x-stat-card icon="banknotes" label="Outstanding Pokok" :value="format_rupiah($dashboard['portfolio']['outstanding_principal'])" accent="amber" />
        <x-stat-card icon="receipt-percent" label="Outstanding Bunga" :value="format_rupiah($dashboard['portfolio']['outstanding_interest'])" accent="amber" />
        <x-stat-card icon="wallet" label="Total Portofolio" :value="format_rupiah($dashboard['portfolio']['portfolio_total'])" accent="green" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-stat-card icon="calendar-days" label="Jatuh Tempo Hari Ini" :value="$dashboard['collection']['due_today_count']" :meta="format_rupiah($dashboard['collection']['due_today_amount'])" accent="blue" />
        <x-stat-card icon="exclamation-triangle" label="Menunggak" :value="$dashboard['collection']['overdue_count']" :meta="format_rupiah($dashboard['collection']['overdue_amount'])" accent="red" />
        <x-stat-card icon="calendar-days" label="Jatuh Tempo Minggu Ini" :value="$dashboard['collection']['due_this_week_count']" accent="amber" />
        <x-stat-card icon="hand-raised" label="Janji Bayar" :value="$dashboard['collection']['promise_count']" :meta="format_rupiah($dashboard['collection']['promise_amount'])" accent="amber" />
        <x-stat-card icon="shield-check" label="Agunan dalam Penyimpanan" :value="$dashboard['collateral']['in_custody']" accent="blue" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card icon="wallet" label="Total Agunan" :value="$dashboard['collateral']['total']" />
        <x-stat-card icon="check-badge" label="Agunan Siap Diambil" :value="$dashboard['collateral']['ready']" accent="green" />
        <x-stat-card icon="arrow-right" label="Agunan Sudah Diserahkan" :value="$dashboard['collateral']['released']" accent="green" />
    </div>

    <flux:card>
        <flux:heading size="lg">Aktivitas Terbaru</flux:heading>
        <flux:subheading>Transaksi lintas modul yang paling baru terjadi.</flux:subheading>

        <div class="mt-4 flex flex-col">
            @forelse ($dashboard['recent'] as $activity)
                <a
                    href="{{ $activity['url'] }}"
                    wire:navigate
                    class="flex items-center gap-4 rounded-lg px-2 py-3 transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800/50"
                >
                    <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-2 dark:border-neutral-700 dark:bg-neutral-800/50">
                        <flux:icon :name="$activity['icon']" variant="outline" class="size-5 text-neutral-500 dark:text-neutral-400" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium">{{ $activity['title'] }}</p>
                        <p class="truncate text-sm text-neutral-500 dark:text-neutral-400">{{ $activity['description'] }}</p>
                    </div>
                    <p class="shrink-0 text-xs text-neutral-400">{{ format_date_indonesian($activity['time'], true) }}</p>
                </a>
            @empty
                <div class="flex flex-col items-center justify-center gap-3 p-8 text-center">
                    <flux:icon name="clock" variant="outline" class="size-8 text-neutral-400" />
                    <flux:text>Belum ada aktivitas untuk ditampilkan.</flux:text>
                </div>
            @endforelse
        </div>
    </flux:card>
</div>
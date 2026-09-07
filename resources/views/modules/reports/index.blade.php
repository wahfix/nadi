<x-layouts::app :title="__('Laporan')">
    @php
        $reports = [
            ['route' => 'reports.customers', 'icon' => 'user-group', 'color' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300', 'title' => 'Daftar Nasabah', 'desc' => 'Seluruh data nasabah terdaftar berikut jumlah pinjamannya.'],
            ['route' => 'reports.loans', 'icon' => 'banknotes', 'color' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300', 'title' => 'Daftar Pinjaman', 'desc' => 'Seluruh kontrak pinjaman dengan status dan nilai pokok.'],
            ['route' => 'reports.outstanding', 'icon' => 'chart-bar', 'color' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300', 'title' => 'Outstanding Pinjaman', 'desc' => 'Portofolio pinjaman berjalan (Aktif & Menunggak) beserta total sisa tagihan.'],
            ['route' => 'reports.due-dates', 'icon' => 'calendar-days', 'color' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', 'title' => 'Jadwal Jatuh Tempo', 'desc' => 'Angsuran yang akan jatuh tempo dalam rentang tanggal.'],
            ['route' => 'reports.overdue', 'icon' => 'exclamation-triangle', 'color' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300', 'title' => 'Tunggakan', 'desc' => 'Angsuran menunggak dengan perhitungan hari keterlambatan (DPD).'],
            ['route' => 'reports.payments', 'icon' => 'currency-dollar', 'color' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300', 'title' => 'Pembayaran', 'desc' => 'Seluruh penerimaan pembayaran beserta alokasi pokok, bunga, dan denda.'],
            ['route' => 'reports.collaterals', 'icon' => 'building-library', 'color' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300', 'title' => 'Jaminan', 'desc' => 'Agunan yang diterima dan status penyimpanannya.'],
            ['route' => 'reports.releases', 'icon' => 'arrow-path-rounded-square', 'color' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-300', 'title' => 'Pengambilan Jaminan', 'desc' => 'Serah terima pelepasan agunan kepada nasabah.'],
            ['route' => 'reports.collection-activities', 'icon' => 'phone', 'color' => 'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-900/40 dark:text-fuchsia-300', 'title' => 'Aktivitas Penagihan', 'desc' => 'Riwayat penagihan, hasil kontak, dan janji bayar.'],
            ['route' => 'reports.audit-logs', 'icon' => 'document-magnifying-glass', 'color' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300', 'title' => 'Audit Log', 'desc' => 'Jejak audit seluruh mutasi data dalam rentang tanggal.'],
        ];
    @endphp

    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:heading size="xl">Laporan</flux:heading>
            <flux:subheading>Sepuluh modul laporan operasional dan finansial dengan filter tanggal dan tampilan ramah cetak.</flux:subheading>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($reports as $report)
                <a
                    href="{{ route($report['route']) }}"
                    wire:navigate
                    class="group flex gap-4 rounded-xl border border-neutral-200 bg-white p-4 transition-colors hover:border-accent hover:bg-accent-content/5 dark:border-neutral-700 dark:bg-zinc-900"
                >
                    <div class="flex aspect-square size-11 shrink-0 items-center justify-center rounded-xl {{ $report['color'] }}">
                        <flux:icon :name="$report['icon']" variant="outline" class="size-5" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <p class="font-medium">{{ $report['title'] }}</p>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ $report['desc'] }}</p>
                    </div>
                    <flux:icon name="arrow-right" class="ml-auto size-4 self-center text-neutral-400 transition-transform group-hover:translate-x-0.5" />
                </a>
            @endforeach
        </div>
    </div>
</x-layouts::app>
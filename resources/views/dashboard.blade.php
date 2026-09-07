@php
    $dashboardUser = auth()->user();
    $quickLinks = collect([
        ['permission' => 'customers.view', 'route' => 'customers.index', 'icon' => 'users', 'title' => 'Nasabah', 'description' => 'Profil debitur dan riwayat pekerjaan.'],
        ['permission' => 'loans.view', 'route' => 'loans.index', 'icon' => 'clipboard-document-list', 'title' => 'Pinjaman', 'description' => 'Pengajuan, review, dan kontrak pinjaman.'],
        ['permission' => 'installments.view', 'route' => 'installments.index', 'icon' => 'calendar-days', 'title' => 'Angsuran', 'description' => 'Jadwal angsuran dan jatuh tempo.'],
        ['permission' => 'payments.view', 'route' => 'payments.index', 'icon' => 'banknotes', 'title' => 'Pembayaran', 'description' => 'Setoran kasir dan kuitansi.'],
        ['permission' => 'collections.view', 'route' => 'collections.index', 'icon' => 'hand-raised', 'title' => 'Penagihan', 'description' => 'Aktivitas kontak dan janji bayar.'],
        ['permission' => 'collaterals.view', 'route' => 'collaterals.index', 'icon' => 'wallet', 'title' => 'Jaminan', 'description' => 'Penerimaan dan penyimpanan agunan.'],
        ['permission' => 'verifications.view', 'route' => 'verifications.index', 'icon' => 'shield-check', 'title' => 'Verifikasi Identitas', 'description' => 'Pemeriksaan identitas pemohon.'],
        ['permission' => 'releases.view', 'route' => 'releases.index', 'icon' => 'check-badge', 'title' => 'Pengambilan Jaminan', 'description' => 'Serah terima agunan berproteksi.'],
        ['permission' => 'users.view', 'route' => 'users.index', 'icon' => 'user-group', 'title' => 'Pengguna', 'description' => 'Akun staf dan peran akses.'],
        ['permission' => 'reports.view', 'route' => 'reports.index', 'icon' => 'chart-bar', 'title' => 'Laporan', 'description' => 'Laporan operasional dan finansial.'],
        ['permission' => 'audit_logs.view', 'route' => 'audit-log.index', 'icon' => 'document-magnifying-glass', 'title' => 'Audit Log', 'description' => 'Jejak audit dengan diff perubahan.'],
    ])->filter(fn ($link) => $dashboardUser->hasPermission($link['permission']));
@endphp

<x-layouts::app :title="__('Dasbor')">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:heading size="xl">Selamat datang, {{ Str::before($dashboardUser->name, ' ') }}</flux:heading>
            <flux:subheading>Dasbor {{ strtolower($dashboardUser->primaryRoleName()) }} — NADI Loan Management System.</flux:subheading>
        </div>

        <x-flash-message type="success" :message="session('success')" />
        <x-flash-message type="error" :message="session('error')" />

        @if ($dashboardPartial)
            @include('dashboards.'.$dashboardPartial, ['dashboard' => $dashboard])
        @endif

        @if ($quickLinks->isEmpty())
            <div class="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-neutral-300 p-10 text-center dark:border-neutral-700">
                <flux:icon name="lock-closed" variant="outline" class="size-10 text-neutral-400" />
                <flux:heading size="lg">Belum ada modul untuk peran ini.</flux:heading>
                <flux:text>Hubungi administrator untuk memetakan izin akses terhadap akun Anda.</flux:text>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($quickLinks as $link)
                    <a
                        href="{{ route($link['route']) }}"
                        wire:navigate
                        class="group rounded-xl border border-neutral-200 bg-white p-4 transition-colors hover:border-accent hover:bg-accent-content/5 dark:border-neutral-700 dark:bg-zinc-900"
                    >
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-2 dark:border-neutral-700 dark:bg-neutral-800/50">
                                <flux:icon :name="$link['icon']" variant="outline" class="size-5 text-neutral-500 dark:text-neutral-400" />
                            </div>
                            <div class="flex-1">
                                <p class="font-medium">{{ $link['title'] }}</p>
                                <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ $link['description'] }}</p>
                            </div>
                            <flux:icon name="arrow-right" class="size-4 text-neutral-400 transition-transform group-hover:translate-x-0.5" />
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts::app>
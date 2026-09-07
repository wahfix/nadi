@php
    $navUser = auth()->user();

    $canViewOperasional = collect([
        'customers.view',
        'loans.view',
        'installments.view',
        'payments.view',
        'collections.view',
    ])->contains(fn ($permission) => $navUser->hasPermission($permission));

    $canViewAgunan = collect([
        'collaterals.view',
        'verifications.view',
        'releases.view',
    ])->contains(fn ($permission) => $navUser->hasPermission($permission));

    $canViewAdministrasi = collect([
        'users.view',
        'reports.view',
        'audit_logs.view',
    ])->contains(fn ($permission) => $navUser->hasPermission($permission));
@endphp

<flux:sidebar.group :heading="__('Utama')" class="grid">
    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
        {{ __('Dasbor') }}
    </flux:sidebar.item>
</flux:sidebar.group>

@if ($canViewOperasional)
    <flux:sidebar.group :heading="__('Operasional')" class="grid">
        @can('customers.view')
            <flux:sidebar.item icon="users" :href="route('customers.index')" :current="request()->routeIs('customers.*')" wire:navigate>
                {{ __('Nasabah') }}
            </flux:sidebar.item>
        @endcan

        @can('loans.view')
            <flux:sidebar.item icon="clipboard-document-list" :href="route('loans.index')" :current="request()->routeIs('loans.*')" wire:navigate>
                {{ __('Pinjaman') }}
            </flux:sidebar.item>
        @endcan

        @can('installments.view')
            <flux:sidebar.item icon="calendar-days" :href="route('installments.index')" :current="request()->routeIs('installments.*')" wire:navigate>
                {{ __('Angsuran') }}
            </flux:sidebar.item>
        @endcan

        @can('payments.view')
            <flux:sidebar.item icon="banknotes" :href="route('payments.index')" :current="request()->routeIs('payments.*')" wire:navigate>
                {{ __('Pembayaran') }}
            </flux:sidebar.item>
        @endcan

        @can('collections.view')
            <flux:sidebar.item icon="hand-raised" :href="route('collections.index')" :current="request()->routeIs('collections.*')" wire:navigate>
                {{ __('Penagihan') }}
            </flux:sidebar.item>
        @endcan
    </flux:sidebar.group>
@endif

@if ($canViewAgunan)
    <flux:sidebar.group :heading="__('Agunan')" class="grid">
        @can('collaterals.view')
            <flux:sidebar.item icon="wallet" :href="route('collaterals.index')" :current="request()->routeIs('collaterals.*')" wire:navigate>
                {{ __('Jaminan') }}
            </flux:sidebar.item>
        @endcan

        @can('verifications.view')
            <flux:sidebar.item icon="shield-check" :href="route('verifications.index')" :current="request()->routeIs('verifications.*')" wire:navigate>
                {{ __('Verifikasi Identitas') }}
            </flux:sidebar.item>
        @endcan

        @can('releases.view')
            <flux:sidebar.item icon="check-badge" :href="route('releases.index')" :current="request()->routeIs('releases.*')" wire:navigate>
                {{ __('Pengambilan Jaminan') }}
            </flux:sidebar.item>
        @endcan
    </flux:sidebar.group>
@endif

@if ($canViewAdministrasi)
    <flux:sidebar.group :heading="__('Administrasi')" class="grid">
        @can('users.view')
            <flux:sidebar.item icon="user-group" :href="route('users.index')" :current="request()->routeIs('users.*')" wire:navigate>
                {{ __('Pengguna') }}
            </flux:sidebar.item>
        @endcan

        @can('reports.view')
            <flux:sidebar.item icon="chart-bar" :href="route('reports.index')" :current="request()->routeIs('reports.*')" wire:navigate>
                {{ __('Laporan') }}
            </flux:sidebar.item>
        @endcan

        @can('audit_logs.view')
            <flux:sidebar.item icon="document-magnifying-glass" :href="route('audit-log.index')" :current="request()->routeIs('audit-log.*')" wire:navigate>
                {{ __('Audit Log') }}
            </flux:sidebar.item>
        @endcan
    </flux:sidebar.group>
@endif

<flux:sidebar.group :heading="__('Sistem')" class="grid">
    <flux:sidebar.item icon="cog-8-tooth" :href="route('settings')" :current="request()->routeIs('settings*')" wire:navigate>
        {{ __('Pengaturan') }}
    </flux:sidebar.item>
</flux:sidebar.group>
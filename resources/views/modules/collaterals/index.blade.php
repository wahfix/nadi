<x-layouts::app :title="__('Jaminan')">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:heading size="xl">Jaminan</flux:heading>
            <flux:subheading>Kelola penerimaan, penyimpanan, dan status agunan jaminan nasabah.</flux:subheading>
        </div>

        <x-flash-message type="success" :message="session('success')" />
        <x-flash-message type="error" :message="session('error')" />

        @can('collaterals.receive')
            <div class="flex items-center justify-end">
                <flux:button as="a" :href="route('collaterals.create')" wire:navigate icon="plus">
                    Terima Jaminan
                </flux:button>
            </div>
        @endcan

        <flux:card>
            <form method="GET" action="{{ route('collaterals.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
                <div class="min-w-64 flex-1">
                    <flux:input
                        type="search"
                        name="search"
                        :value="request('search')"
                        :placeholder="__('Cari kode jaminan, NIK, nama nasabah, atau nomor pinjaman...')"
                    />
                </div>

                <flux:select name="custody_status" class="w-48">
                    <flux:select.option value="">Semua Status</flux:select.option>
                    @foreach ($statusOptions as $status)
                        <flux:select.option :value="$status" :selected="request('custody_status') == $status">
                            {{ str_replace('_', ' ', $status) }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select name="collateral_type" class="w-40">
                    <flux:select.option value="">Semua Jenis</flux:select.option>
                    @foreach ($typeOptions as $value => $label)
                        <flux:select.option :value="$value" :selected="request('collateral_type') == $value">
                            {{ $label }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:button variant="primary" type="submit">Filter</flux:button>

                @if (request()->hasAny(['search', 'custody_status', 'collateral_type']))
                    <flux:button as="a" :href="route('collaterals.index')" wire:navigate variant="ghost">Reset</flux:button>
                @endif
            </form>

            @if ($collaterals->isEmpty())
                <div class="flex flex-col items-center justify-center gap-3 p-10 text-center">
                    <flux:icon name="wallet" variant="outline" class="size-10 text-neutral-400" />
                    <flux:heading size="lg">Belum ada jaminan.</flux:heading>
                    <flux:text>Terima jaminan pertama dari nasabah untuk mulai mengelola agunan.</flux:text>
                    @can('collaterals.receive')
                        <flux:button as="a" :href="route('collaterals.create')" wire:navigate class="mt-4">
                            Terima Jaminan
                        </flux:button>
                    @endcan
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Kode</flux:table.column>
                        <flux:table.column>Nasabah</flux:table.column>
                        <flux:table.column>Pinjaman</flux:table.column>
                        <flux:table.column>Jenis</flux:table.column>
                        <flux:table.column>Nilai Taksasi</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Aksi</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($collaterals as $collateral)
                            <flux:table.row>
                                <flux:table.cell>
                                    <span class="font-mono text-sm">{{ $collateral->collateral_code }}</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <a href="{{ route('customers.show', $collateral->customer) }}" wire:navigate class="font-medium text-neutral-900 hover:underline">
                                        {{ $collateral->customer?->full_name ?? '-' }}
                                    </a>
                                </flux:table.cell>
                                <flux:table.cell class="font-mono text-xs text-neutral-600">{{ $collateral->loan?->loan_number }}</flux:table.cell>
                                <flux:table.cell>{{ $typeOptions[$collateral->collateral_type] ?? $collateral->collateral_type }}</flux:table.cell>
                                <flux:table.cell>{{ format_rupiah($collateral->estimated_value) }}</flux:table.cell>
                                <flux:table.cell>
                                    @php
                                        $statusColors = [
                                            'PENDING' => 'neutral',
                                            'RECEIVED' => 'blue',
                                            'IN_CUSTODY' => 'green',
                                            'READY_FOR_RELEASE' => 'amber',
                                            'RELEASED' => 'emerald',
                                            'DISPUTED' => 'red',
                                        ];
                                    @endphp
                                    <flux:badge :color="$statusColors[$collateral->custody_status] ?? 'neutral'">
                                        {{ str_replace('_', ' ', $collateral->custody_status) }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:button as="a" :href="route('collaterals.show', $collateral)" wire:navigate size="sm" variant="ghost">
                                        Lihat
                                    </flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <div class="mt-4">
                    {{ $collaterals->links() }}
                </div>
            @endif
        </flux:card>
    </div>
</x-layouts::app>

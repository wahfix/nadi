<x-layouts::app :title="__('Pinjaman')">
    @php
        $statusLabels = [
            'DRAFT' => 'Draft',
            'SUBMITTED' => 'Disubmit',
            'UNDER_REVIEW' => 'Dalam Review',
            'APPROVED' => 'Disetujui',
            'REJECTED' => 'Ditolak',
            'READY_FOR_DISBURSEMENT' => 'Siap Dicairkan',
            'ACTIVE' => 'Aktif',
            'OVERDUE' => 'Menunggak',
            'COMPLETED' => 'Lunas',
            'DEFAULTED' => 'Macet',
            'CANCELLED' => 'Dibatalkan',
        ];
        $statusColors = [
            'DRAFT' => 'neutral',
            'SUBMITTED' => 'blue',
            'UNDER_REVIEW' => 'amber',
            'APPROVED' => 'blue',
            'REJECTED' => 'red',
            'READY_FOR_DISBURSEMENT' => 'blue',
            'ACTIVE' => 'green',
            'OVERDUE' => 'amber',
            'COMPLETED' => 'green',
            'DEFAULTED' => 'red',
            'CANCELLED' => 'neutral',
        ];
    @endphp

    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:heading size="xl">Pinjaman</flux:heading>
            <flux:subheading>Pengajuan, review, persetujuan, dan pencairan kontrak pinjaman.</flux:subheading>
        </div>

        <x-flash-message type="success" :message="session('success')" />
        <x-flash-message type="error" :message="session('error')" />

        @can('loans.create')
            <div class="flex items-center justify-end">
                <flux:button as="a" :href="route('loans.create')" wire:navigate icon="plus">
                    Ajukan Pinjaman
                </flux:button>
            </div>
        @endcan

        <flux:card>
            <form method="GET" action="{{ route('loans.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
                <div class="min-w-64 flex-1">
                    <flux:input
                        type="search"
                        name="search"
                        :value="request('search')"
                        :placeholder="__('Cari nomor pinjaman, nama, atau kode nasabah...')"
                    />
                </div>

                <flux:select name="status" class="w-48">
                    <flux:select.option value="">Semua Status</flux:select.option>
                    @foreach ($statusOptions as $status)
                        <flux:select.option :value="$status" :selected="request('status') == $status">
                            {{ $statusLabels[$status] ?? $status }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select name="customer_id" class="w-56">
                    <flux:select.option value="">Semua Nasabah</flux:select.option>
                    @foreach ($customers as $customer)
                        <flux:select.option :value="$customer->id" :selected="request('customer_id') == $customer->id">
                            {{ $customer->full_name }} ({{ $customer->customer_code }})
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:button variant="primary" type="submit">
                    Filter
                </flux:button>

                @if (request()->hasAny(['search', 'status', 'customer_id']))
                    <flux:button as="a" :href="route('loans.index')" wire:navigate variant="ghost">
                        Reset
                    </flux:button>
                @endif
            </form>

            @if ($loans->isEmpty())
                <div class="flex flex-col items-center justify-center gap-3 p-10 text-center">
                    <flux:icon name="clipboard-document-list" variant="outline" class="size-10 text-neutral-400" />
                    <flux:heading size="lg">Belum ada pinjaman.</flux:heading>
                    <flux:text>Ajukan pinjaman pertama untuk mulai membangun portofolio nasabah.</flux:text>
                    @can('loans.create')
                        <flux:button as="a" :href="route('loans.create')" wire:navigate class="mt-4">
                            Ajukan Pinjaman
                        </flux:button>
                    @endcan
                </div>
            @else
                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Nomor</flux:table.column>
                            <flux:table.column>Nasabah</flux:table.column>
                            <flux:table.column>Pokok</flux:table.column>
                            <flux:table.column>Angsuran</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column>Aksi</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($loans as $loan)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <a href="{{ route('loans.show', $loan) }}" wire:navigate class="font-mono text-sm font-medium text-neutral-900 hover:underline">
                                            {{ $loan->loan_number }}
                                        </a>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <a href="{{ route('customers.show', $loan->customer) }}" wire:navigate class="font-medium hover:underline">
                                            {{ $loan->customer?->full_name ?? '-' }}
                                        </a>
                                        <div class="text-xs text-neutral-500">{{ $loan->customer?->customer_code }}</div>
                                    </flux:table.cell>
                                    <flux:table.cell>{{ format_rupiah($loan->principal_amount) }}</flux:table.cell>
                                    <flux:table.cell>
                                        {{ format_rupiah($loan->installment_amount) }}
                                        <div class="text-xs text-neutral-500">
                                            / {{ $loan->installment_frequency === 'WEEKLY' ? 'minggu' : 'bulan' }} · {{ $loan->tenor }}x
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge :color="$statusColors[$loan->status] ?? 'neutral'">
                                            {{ $statusLabels[$loan->status] ?? $loan->status }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:button as="a" :href="route('loans.show', $loan)" wire:navigate size="sm" variant="ghost">
                                            Lihat
                                        </flux:button>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                <div class="mt-4">
                    {{ $loans->links() }}
                </div>
            @endif
        </flux:card>
    </div>
</x-layouts::app>
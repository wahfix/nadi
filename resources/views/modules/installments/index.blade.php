<x-layouts::app :title="__('Angsuran')">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:heading size="xl">Angsuran</flux:heading>
            <flux:subheading>Pantau jadwal angsuran, jatuh tempo, dan tunggakan semua pinjaman aktif.</flux:subheading>
        </div>

        <x-flash-message type="success" :message="session('success')" />
        <x-flash-message type="error" :message="session('error')" />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <flux:card class="border-red-200 dark:border-red-800">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-red-50 p-2 dark:bg-red-900/30">
                        <flux:icon name="exclamation-triangle" variant="outline" class="size-5 text-red-500" />
                    </div>
                    <div>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">Menunggak</p>
                        <p class="text-xl font-semibold text-red-600">{{ $summary['overdue_count'] }}</p>
                        <p class="text-xs text-red-500">{{ format_rupiah($summary['overdue_total']) }}</p>
                    </div>
                </div>
            </flux:card>

            <flux:card class="border-amber-200 dark:border-amber-800">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-amber-50 p-2 dark:bg-amber-900/30">
                        <flux:icon name="calendar-days" variant="outline" class="size-5 text-amber-500" />
                    </div>
                    <div>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">Jatuh Tempo Bulan Ini</p>
                        <p class="text-xl font-semibold text-amber-600">{{ $summary['due_this_month_count'] }}</p>
                        <p class="text-xs text-amber-500">{{ format_rupiah($summary['due_this_month_total']) }}</p>
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-blue-50 p-2 dark:bg-blue-900/30">
                        <flux:icon name="banknotes" variant="outline" class="size-5 text-blue-500" />
                    </div>
                    <div>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">Sisa Tagihan Aktif</p>
                        <p class="text-xl font-semibold">{{ format_rupiah($summary['active_remaining']) }}</p>
                    </div>
                </div>
            </flux:card>
        </div>

        <flux:card>
            <form method="GET" action="{{ route('installments.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
                <div class="min-w-64 flex-1">
                    <flux:input
                        type="search"
                        name="search"
                        :value="request('search')"
                        :placeholder="__('Cari nomor pinjaman, nama nasabah, atau angsuran ke-...')"
                    />
                </div>

                <flux:select name="status" class="w-56">
                    <flux:select.option value="">Semua Status</flux:select.option>
                    @foreach ($statusOptions as $status)
                        <flux:select.option :value="$status" :selected="request('status') == $status">
                            {{ $statusLabels[$status] }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:button variant="primary" type="submit">Filter</flux:button>

                @if (request()->hasAny(['search', 'status']))
                    <flux:button as="a" :href="route('installments.index')" wire:navigate variant="ghost">Reset</flux:button>
                @endif
            </form>

            @if ($installments->isEmpty())
                <div class="flex flex-col items-center justify-center gap-3 p-10 text-center">
                    <flux:icon name="calendar-days" variant="outline" class="size-10 text-neutral-400" />
                    <flux:heading size="lg">Tidak ada angsuran yang ditemukan.</flux:heading>
                    <flux:text>Tidak ada jadwal angsuran untuk pinjaman aktif dengan kriteria pencarian ini.</flux:text>
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Nasabah</flux:table.column>
                        <flux:table.column>Pinjaman</flux:table.column>
                        <flux:table.column>Angsuran</flux:table.column>
                        <flux:table.column>Jatuh Tempo</flux:table.column>
                        <flux:table.column>Total Tagihan</flux:table.column>
                        <flux:table.column>Terbayar</flux:table.column>
                        <flux:table.column>Sisa</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Aksi</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($installments as $installment)
                            <flux:table.row>
                                <flux:table.cell>
                                    <a href="{{ route('customers.show', $installment->loan->customer) }}" wire:navigate class="font-medium text-neutral-900 hover:underline">
                                        {{ $installment->loan->customer?->full_name ?? '-' }}
                                    </a>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <a href="{{ route('loans.show', $installment->loan) }}" wire:navigate class="font-mono text-xs text-neutral-600 hover:underline">
                                        {{ $installment->loan->loan_number }}
                                    </a>
                                </flux:table.cell>
                                <flux:table.cell class="font-mono text-sm">
                                    {{ $installment->installment_number }}/{{ $installment->loan->tenor }}
                                </flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">
                                    {{ format_date($installment->due_date) }}
                                </flux:table.cell>
                                <flux:table.cell>{{ format_rupiah($installment->total_due) }}</flux:table.cell>
                                <flux:table.cell>{{ format_rupiah($installment->total_paid) }}</flux:table.cell>
                                <flux:table.cell @class([
                                    'font-medium',
                                    'text-red-600' => $installment->remaining_amount > 0 && $installment->status === 'OVERDUE',
                                ])>
                                    {{ format_rupiah($installment->remaining_amount) }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    @php
                                        $statusColors = [
                                            'PENDING' => 'neutral',
                                            'PARTIALLY_PAID' => 'amber',
                                            'PAID' => 'emerald',
                                            'OVERDUE' => 'red',
                                            'WAIVED' => 'neutral',
                                        ];
                                    @endphp
                                    <flux:badge :color="$statusColors[$installment->status] ?? 'neutral'">
                                        {{ $statusLabels[$installment->status] }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:button as="a" :href="route('installments.show', $installment)" wire:navigate size="sm" variant="ghost">
                                        Lihat
                                    </flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <div class="mt-4">
                    {{ $installments->links() }}
                </div>
            @endif
        </flux:card>
    </div>
</x-layouts::app>
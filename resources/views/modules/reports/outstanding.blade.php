<x-report-frame :title="'Laporan Outstanding Pinjaman'" subtitle="Portofolio pinjaman berjalan (Aktif & Menunggak) berikut sisa tagihan per nasabah." :reportName="'Outstanding Pinjaman'">
    <x-slot name="filters">
        <x-report-filter
            :route="route('reports.outstanding')"
            search-label="Cari nama nasabah..."
        />
    </x-slot>

    @if ($loans->isEmpty())
        <div class="flex flex-col items-center justify-center gap-3 p-10 text-center">
            <flux:icon name="chart-bar" variant="outline" class="size-10 text-neutral-400" />
            <flux:heading size="lg">Tidak ada pinjaman berjalan.</flux:heading>
            <flux:text>Seluruh pinjaman lunas atau belum dicairkan.</flux:text>
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Nasabah</flux:table.column>
                <flux:table.column>No. Pinjaman</flux:table.column>
                <flux:table.column>Pokok</flux:table.column>
                <flux:table.column>Sisa Pokok</flux:table.column>
                <flux:table.column>Sisa Bunga</flux:table.column>
                <flux:table.column>Sisa Denda</flux:table.column>
                <flux:table.column>Total Sisa</flux:table.column>
                <flux:table.column>Status</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($loans as $loan)
                    <flux:table.row>
                        <flux:table.cell class="font-medium">{{ $loan->customer?->full_name ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="font-mono text-xs">{{ $loan->loan_number }}</flux:table.cell>
                        <flux:table.cell>{{ format_rupiah($loan->principal_amount) }}</flux:table.cell>
                        <flux:table.cell>{{ format_rupiah($loan->outstanding_principal) }}</flux:table.cell>
                        <flux:table.cell>{{ format_rupiah($loan->outstanding_interest) }}</flux:table.cell>
                        <flux:table.cell>{{ format_rupiah($loan->outstanding_penalty) }}</flux:table.cell>
                        <flux:table.cell class="font-semibold">{{ format_rupiah($loan->outstanding_total) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$statusColors[$loan->status] ?? 'neutral'">{{ $statusLabels[$loan->status] ?? $loan->status }}</flux:badge>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>

            <flux:table.rows>
                <flux:table.row class="bg-neutral-50 font-semibold dark:bg-neutral-800">
                    <flux:table.cell colspan="3">Total ({{ $totals['count'] }} pinjaman)</flux:table.cell>
                    <flux:table.cell>{{ format_rupiah($totals['principal_amount']) }}</flux:table.cell>
                    <flux:table.cell colspan="3"></flux:table.cell>
                    <flux:table.cell>{{ format_rupiah($totals['outstanding_total']) }}</flux:table.cell>
                </flux:table.row>
            </flux:table.rows>
        </flux:table>

        <div class="pagination-links mt-4">{{ $loans->links() }}</div>
    @endif
</x-report-frame>
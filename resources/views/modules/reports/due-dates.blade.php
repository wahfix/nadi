<x-report-frame :title="'Laporan Jadwal Jatuh Tempo'" subtitle="Angsuran yang belum lunas beserta tanggal jatuh temponya dalam rentang tanggal." :reportName="'Jadwal Jatuh Tempo'">
    @php
        $statusLabels = [
            'PENDING' => 'Belum Bayar',
            'PARTIALLY_PAID' => 'Sebagian',
            'OVERDUE' => 'Tunggak',
        ];
        $statusColors = [
            'PENDING' => 'neutral',
            'PARTIALLY_PAID' => 'amber',
            'OVERDUE' => 'red',
        ];
    @endphp

    <x-slot name="filters">
        <x-report-filter
            :route="route('reports.due-dates')"
            :status-options="$statusLabels"
        />
    </x-slot>

    @if ($installments->isEmpty())
        <div class="flex flex-col items-center justify-center gap-3 p-10 text-center">
            <flux:icon name="calendar-days" variant="outline" class="size-10 text-neutral-400" />
            <flux:heading size="lg">Tidak ada angsuran jatuh tempo.</flux:heading>
            <flux:text>Belum ada angsuran yang sesuai dengan filter ini.</flux:text>
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Nasabah</flux:table.column>
                <flux:table.column>No. Pinjaman</flux:table.column>
                <flux:table.column>Angsuran</flux:table.column>
                <flux:table.column>Jatuh Tempo</flux:table.column>
                <flux:table.column>Total Angsuran</flux:table.column>
                <flux:table.column>Sisa Tagihan</flux:table.column>
                <flux:table.column>Status</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($installments as $installment)
                    <flux:table.row>
                        <flux:table.cell class="font-medium">{{ $installment->loan?->customer?->full_name ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="font-mono text-xs">{{ $installment->loan?->loan_number ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">Ke-{{ $installment->installment_number }}</flux:table.cell>
                        <flux:table.cell>{{ format_date($installment->due_date) }}</flux:table.cell>
                        <flux:table.cell>{{ format_rupiah($installment->total_amount) }}</flux:table.cell>
                        <flux:table.cell>{{ format_rupiah($installment->remaining_amount) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$statusColors[$installment->status] ?? 'neutral'">{{ $statusLabels[$installment->status] ?? $installment->status }}</flux:badge>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="pagination-links mt-4">{{ $installments->links() }}</div>
    @endif
</x-report-frame>
<x-report-frame :title="'Laporan Tunggakan'" subtitle="Angsuran menunggak pada pinjaman berjalan dengan perhitungan hari keterlambatan (DPD)." :reportName="'Tunggakan'">
    <x-slot name="filters">
        <x-report-filter
            :route="route('reports.overdue')"
            search-label="Cari nama nasabah..."
        />
    </x-slot>

    @if ($installments->isEmpty())
        <div class="flex flex-col items-center justify-center gap-3 p-10 text-center">
            <flux:icon name="exclamation-triangle" variant="outline" class="size-10 text-neutral-400" />
            <flux:heading size="lg">Tidak ada tunggakan.</flux:heading>
            <flux:text>Tidak ada angsuran menunggak yang sesuai dengan filter ini.</flux:text>
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Nasabah</flux:table.column>
                <flux:table.column>No. Pinjaman</flux:table.column>
                <flux:table.column>Angsuran</flux:table.column>
                <flux:table.column>Jatuh Tempo</flux:table.column>
                <flux:table.column>DPD (Hari)</flux:table.column>
                <flux:table.column>Sisa Tagihan</flux:table.column>
                <flux:table.column>Status Pinjaman</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($installments as $installment)
                    <flux:table.row>
                        <flux:table.cell class="font-medium">{{ $installment->loan?->customer?->full_name ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="font-mono text-xs">{{ $installment->loan?->loan_number ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">Ke-{{ $installment->installment_number }}</flux:table.cell>
                        <flux:table.cell>{{ format_date($installment->due_date) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="red">{{ $installment->due_date->diffInDays(now()) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="font-semibold text-red-600 dark:text-red-400">{{ format_rupiah($installment->remaining_amount) }}</flux:table.cell>
                        <flux:table.cell>{{ $installment->loan?->status ?? '—' }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="pagination-links mt-4">{{ $installments->links() }}</div>
    @endif
</x-report-frame>
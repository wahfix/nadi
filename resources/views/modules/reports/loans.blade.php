<x-report-frame :title="'Laporan Daftar Pinjaman'" subtitle="Seluruh kontrak pinjaman dengan status, metode bunga, dan nilai pokok." :reportName="'Daftar Pinjaman'">
    <x-slot name="filters">
        <x-report-filter
            :route="route('reports.loans')"
            search-label="Cari nomor pinjaman atau nama nasabah..."
            :status-options="$statusLabels"
        />
    </x-slot>

    @if ($loans->isEmpty())
        <div class="flex flex-col items-center justify-center gap-3 p-10 text-center">
            <flux:icon name="banknotes" variant="outline" class="size-10 text-neutral-400" />
            <flux:heading size="lg">Tidak ada data pinjaman.</flux:heading>
            <flux:text>Belum ada pinjaman yang sesuai dengan filter ini.</flux:text>
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>No. Pinjaman</flux:table.column>
                <flux:table.column>Nasabah</flux:table.column>
                <flux:table.column>Pokok</flux:table.column>
                <flux:table.column>Bunga & Tenor</flux:table.column>
                <flux:table.column>Angsuran</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Pengajuan</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($loans as $loan)
                    <flux:table.row>
                        <flux:table.cell class="font-mono text-xs">{{ $loan->loan_number }}</flux:table.cell>
                        <flux:table.cell class="font-medium">{{ $loan->customer?->full_name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ format_rupiah($loan->principal_amount) }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">
                            {{ $loan->interest_method === 'FLAT' ? 'Flat' : 'Menurun Efektif' }} • {{ format_interest_rate($loan->interest_rate) }} • {{ $loan->tenor }}x
                        </flux:table.cell>
                        <flux:table.cell>{{ format_rupiah($loan->installment_amount) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$statusColors[$loan->status] ?? 'neutral'">{{ $statusLabels[$loan->status] ?? $loan->status }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ format_date($loan->created_at) }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="pagination-links mt-4">{{ $loans->links() }}</div>
    @endif
</x-report-frame>
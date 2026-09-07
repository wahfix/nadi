<x-report-frame :title="'Laporan Jaminan'" subtitle="Agunan yang diterima, jenis, taksasi, dan status penyimpanannya." :reportName="'Jaminan'">
    <x-slot name="filters">
        <x-report-filter
            :route="route('reports.collaterals')"
            search-label="Cari nomor jaminan atau nama nasabah..."
            :status-options="$statusLabels"
        />
    </x-slot>

    @if ($collaterals->isEmpty())
        <div class="flex flex-col items-center justify-center gap-3 p-10 text-center">
            <flux:icon name="building-library" variant="outline" class="size-10 text-neutral-400" />
            <flux:heading size="lg">Tidak ada data jaminan.</flux:heading>
            <flux:text>Belum ada jaminan yang sesuai dengan filter ini.</flux:text>
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>No. Jaminan</flux:table.column>
                <flux:table.column>Nasabah</flux:table.column>
                <flux:table.column>No. Pinjaman</flux:table.column>
                <flux:table.column>Jenis</flux:table.column>
                <flux:table.column>Lokasi Penyimpanan</flux:table.column>
                <flux:table.column>Taksasi</flux:table.column>
                <flux:table.column>Diterima</flux:table.column>
                <flux:table.column>Status</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($collaterals as $collateral)
                    <flux:table.row>
                        <flux:table.cell class="font-mono text-xs">{{ $collateral->collateral_code }}</flux:table.cell>
                        <flux:table.cell class="font-medium">{{ $collateral->customer?->full_name ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="font-mono text-xs">{{ $collateral->loan?->loan_number ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $typeLabels[$collateral->collateral_type] ?? $collateral->collateral_type }}</flux:table.cell>
                        <flux:table.cell>{{ $collateral->storage_location }}</flux:table.cell>
                        <flux:table.cell>{{ format_rupiah($collateral->estimated_value) }}</flux:table.cell>
                        <flux:table.cell>{{ format_date($collateral->received_date) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$collateral->custody_status === 'RELEASED' ? 'green' : ($collateral->custody_status === 'DISPUTED' ? 'red' : 'amber')">{{ $statusLabels[$collateral->custody_status] ?? $collateral->custody_status }}</flux:badge>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="pagination-links mt-4">{{ $collaterals->links() }}</div>
    @endif
</x-report-frame>
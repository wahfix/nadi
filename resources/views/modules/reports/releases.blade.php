<x-report-frame :title="'Laporan Pengambilan Jaminan'" subtitle="Serah terima pelepasan agunan beserta penerima dan lokasi penyerahan." :reportName="'Pengambilan Jaminan'">
    <x-slot name="filters">
        <x-report-filter
            :route="route('reports.releases')"
            search-label="Cari nomor pengambilan, penerima, atau nama nasabah..."
        />
    </x-slot>

    @if ($releases->isEmpty())
        <div class="flex flex-col items-center justify-center gap-3 p-10 text-center">
            <flux:icon name="arrow-path-rounded-square" variant="outline" class="size-10 text-neutral-400" />
            <flux:heading size="lg">Tidak ada data pengambilan.</flux:heading>
            <flux:text>Belum ada serah terima pelepasan yang sesuai dengan filter ini.</flux:text>
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>No. Pengambilan</flux:table.column>
                <flux:table.column>Nasabah</flux:table.column>
                <flux:table.column>No. Jaminan</flux:table.column>
                <flux:table.column>Penerima</flux:table.column>
                <flux:table.column>Hubungan</flux:table.column>
                <flux:table.column>Tanggal</flux:table.column>
                <flux:table.column>Lokasi Penyerahan</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($releases as $release)
                    <flux:table.row>
                        <flux:table.cell class="font-mono text-xs">{{ $release->release_number }}</flux:table.cell>
                        <flux:table.cell class="font-medium">{{ $release->customer?->full_name ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="font-mono text-xs">{{ $release->collateral?->collateral_code ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $release->released_to_name }}</flux:table.cell>
                        <flux:table.cell>{{ $release->relationship_to_customer }}</flux:table.cell>
                        <flux:table.cell>{{ format_date($release->release_date) }}</flux:table.cell>
                        <flux:table.cell>{{ $release->release_location }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="pagination-links mt-4">{{ $releases->links() }}</div>
    @endif
</x-report-frame>
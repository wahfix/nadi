<x-report-frame :title="'Laporan Daftar Nasabah'" subtitle="Seluruh nasabah terdaftar beserta jumlah kontrak pinjamannya." :reportName="'Daftar Nasabah'">
    <x-slot name="filters">
        <x-report-filter
            :route="route('reports.customers')"
            search-label="Cari nomor nasabah, nama, atau NIK..."
        />
    </x-slot>

    @if ($customers->isEmpty())
        <div class="flex flex-col items-center justify-center gap-3 p-10 text-center">
            <flux:icon name="user-group" variant="outline" class="size-10 text-neutral-400" />
            <flux:heading size="lg">Tidak ada data nasabah.</flux:heading>
            <flux:text>Belum ada nasabah yang sesuai dengan filter ini.</flux:text>
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Nomor</flux:table.column>
                <flux:table.column>Nama Lengkap</flux:table.column>
                <flux:table.column>No. Identitas</flux:table.column>
                <flux:table.column>Telepon</flux:table.column>
                <flux:table.column>Kota</flux:table.column>
                <flux:table.column>Pinjaman</flux:table.column>
                <flux:table.column>Terdaftar</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($customers as $customer)
                    <flux:table.row>
                        <flux:table.cell class="font-mono text-xs">{{ $customer->customer_code }}</flux:table.cell>
                        <flux:table.cell class="font-medium">{{ $customer->full_name }}</flux:table.cell>
                        <flux:table.cell class="font-mono text-xs">{{ $customer->id_number }}</flux:table.cell>
                        <flux:table.cell>{{ $customer->phone }}</flux:table.cell>
                        <flux:table.cell>{{ $customer->city }}</flux:table.cell>
                        <flux:table.cell>{{ $customer->loans_count }}</flux:table.cell>
                        <flux:table.cell>{{ format_date($customer->created_at) }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="pagination-links mt-4">{{ $customers->links() }}</div>
    @endif
</x-report-frame>
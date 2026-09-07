<x-report-frame :title="'Laporan Pembayaran'" subtitle="Seluruh penerimaan pembayaran beserta alokasi pokok, bunga, denda, dan metode pembayaran." :reportName="'Pembayaran'">
    @php
        $methodOptions = [
            'CASH' => 'Tunai',
            'BANK_TRANSFER' => 'Transfer Bank',
            'QRIS' => 'QRIS',
            'OTHER' => 'Lainnya',
        ];
    @endphp

    <x-slot name="filters">
        <x-report-filter
            :route="route('reports.payments')"
            search-label="Cari nomor pembayaran, nasabah, atau no. pinjaman..."
            status-param="method"
            :status-options="$methodOptions"
        />
    </x-slot>

    @if ($payments->isEmpty())
        <div class="flex flex-col items-center justify-center gap-3 p-10 text-center">
            <flux:icon name="currency-dollar" variant="outline" class="size-10 text-neutral-400" />
            <flux:heading size="lg">Tidak ada data pembayaran.</flux:heading>
            <flux:text>Belum ada pembayaran yang sesuai dengan filter ini.</flux:text>
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>No. Pembayaran</flux:table.column>
                <flux:table.column>Nasabah</flux:table.column>
                <flux:table.column>Tanggal</flux:table.column>
                <flux:table.column>Metode</flux:table.column>
                <flux:table.column>Pokok</flux:table.column>
                <flux:table.column>Bunga</flux:table.column>
                <flux:table.column>Denda</flux:table.column>
                <flux:table.column>Total</flux:table.column>
                <flux:table.column>Status</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($payments as $payment)
                    <flux:table.row>
                        <flux:table.cell class="font-mono text-xs">{{ $payment->payment_number }}</flux:table.cell>
                        <flux:table.cell class="font-medium">{{ $payment->customer?->full_name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ format_date($payment->payment_date) }}</flux:table.cell>
                        <flux:table.cell>{{ $methodLabels[$payment->payment_method] ?? $payment->payment_method }}</flux:table.cell>
                        <flux:table.cell>{{ format_rupiah($payment->principal_component) }}</flux:table.cell>
                        <flux:table.cell>{{ format_rupiah($payment->interest_component) }}</flux:table.cell>
                        <flux:table.cell>{{ format_rupiah($payment->penalty_component) }}</flux:table.cell>
                        <flux:table.cell class="font-semibold">{{ format_rupiah($payment->amount) }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($payment->isReversed())
                                <flux:badge color="red">Dibalik</flux:badge>
                            @else
                                <flux:badge color="green">Tersimpan</flux:badge>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="pagination-links mt-4">{{ $payments->links() }}</div>
    @endif
</x-report-frame>
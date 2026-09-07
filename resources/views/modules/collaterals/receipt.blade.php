<x-layouts::app :title="'Surat Tanda Terima Jaminan '.$collateral->collateral_code">
    @include('partials.print-app')

    @php
        $typeLabels = [
            'DOCUMENT' => 'Dokumen',
            'VEHICLE' => 'Kendaraan',
            'ELECTRONIC' => 'Elektronik',
            'OTHER' => 'Lainnya',
        ];
    @endphp

    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-col gap-1.5">
                <flux:heading size="xl">Surat Tanda Terima Jaminan</flux:heading>
                <flux:subheading>{{ $collateral->collateral_code }}</flux:subheading>
            </div>
            <flux:button onclick="window.print()" icon="printer" variant="primary" class="no-print">
                Cetak
            </flux:button>
        </div>

        <flux:card>
            <div class="text-center border-b-2 border-neutral-900 pb-4 mb-6">
                <h1 class="text-2xl font-bold tracking-wide">NADI</h1>
                <p class="text-sm text-neutral-500">Loan Management System</p>
            </div>

            <h2 class="text-lg font-bold text-center mb-6 uppercase tracking-wider">Surat Tanda Terima Jaminan</h2>

            <div class="mb-4 text-sm">
                <p>Nomor: <strong class="font-mono">{{ $collateral->collateral_code }}</strong></p>
                <p>Tanggal Terima: <strong>{{ format_date_indonesian($collateral->received_date) }}</strong></p>
            </div>

            <div class="text-sm leading-relaxed space-y-3">
                <p>Pada hari ini, telah diterima agunan dari nasabah dengan rincian sebagai berikut:</p>

                <table class="w-full text-sm my-4">
                    <tr>
                        <td class="py-1 pr-4 w-1/3 text-neutral-500">Nama Nasabah</td>
                        <td class="py-1">: <strong>{{ $collateral->customer?->full_name ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Nomor Nasabah</td>
                        <td class="py-1">: <span class="font-mono">{{ $collateral->customer?->customer_code ?? '-' }}</span></td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Nomor Identitas</td>
                        <td class="py-1">: <span class="font-mono">{{ $collateral->customer?->id_number ?? '-' }}</span></td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">No. Pinjaman</td>
                        <td class="py-1">: <span class="font-mono">{{ $collateral->loan?->loan_number ?? '-' }}</span></td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Jenis Agunan</td>
                        <td class="py-1">: {{ $typeLabels[$collateral->collateral_type] ?? $collateral->collateral_type }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Deskripsi Agunan</td>
                        <td class="py-1">: {{ $collateral->description }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Nomor Identifikasi</td>
                        <td class="py-1">: <span class="font-mono">{{ $collateral->identification_number }}</span></td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Nilai Taksasi</td>
                        <td class="py-1">: <strong>{{ format_rupiah($collateral->estimated_value) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Kondisi Saat Diterima</td>
                        <td class="py-1">: {{ $collateral->condition_on_receipt ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Lokasi Penyimpanan</td>
                        <td class="py-1">: {{ $collateral->storage_location }}</td>
                    </tr>
                </table>

                <p>Agunan tersebut disimpan oleh pihak NADI selama masa pinjaman dan akan dikembalikan setelah seluruh kewajiban nasabah terselesaikan.</p>

                <p>Demikian surat tanda terima ini dibuat dengan sebenar-benarnya.</p>
            </div>

            <div class="mt-12 grid grid-cols-2 gap-8 text-sm text-center">
                <div>
                    <p class="font-semibold">Nasabah (Penyerah)</p>
                    <div class="mt-20 border-t border-neutral-400 pt-2">
                        <p>{{ $collateral->customer?->full_name ?? '-' }}</p>
                    </div>
                </div>
                <div>
                    <p class="font-semibold">Petugas Penerima</p>
                    <div class="mt-20 border-t border-neutral-400 pt-2">
                        <p>{{ $collateral->receivedBy?->name ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </flux:card>
    </div>
</x-layouts::app>
<x-layouts::app :title="'Berita Acara '.$release->release_number">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('releases.index')" wire:navigate>Pengambilan Jaminan</flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route('releases.show', $release)" wire:navigate>{{ $release->release_number }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Berita Acara</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <div class="flex items-center justify-between">
                <flux:heading size="xl">Berita Acara Serah Terima Jaminan</flux:heading>
                <flux:button onclick="window.print()" icon="printer" variant="primary">
                    Cetak
                </flux:button>
            </div>
        </div>

        <flux:card class="print:shadow-none print:border print:border-neutral-300">
            {{-- Kop Surat --}}
            <div class="text-center border-b-2 border-neutral-900 pb-4 mb-6">
                <h1 class="text-2xl font-bold tracking-wide">NADI</h1>
                <p class="text-sm text-neutral-500">Loan Management System</p>
                <p class="mt-1 text-xs text-neutral-400">Terpercaya • Transparan • Tertata</p>
            </div>

            <h2 class="text-lg font-bold text-center mb-6 uppercase tracking-wider">
                Berita Acara Serah Terima Jaminan
            </h2>

            <div class="mb-4 text-sm">
                <p>Nomor: <strong class="font-mono">{{ $release->release_number }}</strong></p>
                <p>Tanggal: <strong>{{ format_date_indonesian($release->release_date) }}</strong></p>
            </div>

            <div class="text-sm leading-relaxed space-y-3">
                <p>Pada hari ini, <strong>{{ format_date_indonesian($release->release_date) }}</strong>, telah dilakukan serah terima jaminan dengan ketentuan sebagai berikut:</p>

                <table class="w-full text-sm my-4">
                    <tr>
                        <td class="py-1 pr-4 w-1/3 text-neutral-500">Nama Nasabah</td>
                        <td class="py-1">: <strong>{{ $release->customer?->full_name ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Nomor Pinjaman</td>
                        <td class="py-1">: <span class="font-mono">{{ $release->loan?->loan_number ?? '-' }}</span></td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Kode Jaminan</td>
                        <td class="py-1">: <span class="font-mono">{{ $release->collateral?->collateral_code ?? '-' }}</span></td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Jenis Agunan</td>
                        <td class="py-1">: {{ $release->collateral?->collateral_type ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Deskripsi Agunan</td>
                        <td class="py-1">: {{ $release->collateral?->description ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Nomor Identifikasi</td>
                        <td class="py-1">: <span class="font-mono">{{ $release->collateral?->identification_number ?? '-' }}</span></td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Diterima Oleh</td>
                        <td class="py-1">: <strong>{{ $release->released_to_name }}</strong></td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Hubungan dengan Nasabah</td>
                        <td class="py-1">: {{ $release->relationship_to_customer }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Lokasi Penyerahan</td>
                        <td class="py-1">: {{ $release->release_location }}</td>
                    </tr>
                </table>

                @if ($release->handover_notes)
                    <p>Catatan: {{ $release->handover_notes }}</p>
                @endif

                <p>Demikian berita acara ini dibuat dengan sebenar-benarnya untuk dapat dipergunakan sebagaimana mestinya.</p>
            </div>

            {{-- Tanda Tangan --}}
            <div class="mt-12 grid grid-cols-3 gap-8 text-sm text-center">
                <div>
                    <p class="font-semibold">Petugas Penyerah</p>
                    <div class="mt-20 border-t border-neutral-400 pt-2">
                        <p>{{ $release->releasedBy?->name ?? '-' }}</p>
                    </div>
                </div>
                <div>
                    <p class="font-semibold">Penerima Jaminan</p>
                    <div class="mt-20 border-t border-neutral-400 pt-2">
                        <p>{{ $release->released_to_name }}</p>
                    </div>
                </div>
                <div>
                    <p class="font-semibold">Saksi</p>
                    <div class="mt-20 border-t border-neutral-400 pt-2">
                        <p>{{ $release->witness?->name ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </flux:card>
    </div>

    <style>
        @media print {
            body * { visibility: hidden; }
            .print\:shadow-none, .print\:shadow-none * { visibility: visible; }
            .print\:shadow-none { position: absolute; left: 0; top: 0; width: 100%; }
        }
    </style>
</x-layouts::app>

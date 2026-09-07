<x-layouts::app :title="'Hasil Verifikasi Identitas'">
    @include('partials.print-app')

    @php
        $methodLabels = [
            'GOVERNMENT_ID' => 'Identitas Pemerintah',
            'ACCOUNT_MATCH' => 'Pencocokan Akun',
            'MANUAL_CHECK' => 'Pemeriksaan Manual',
            'OTHER' => 'Lainnya',
        ];
        $resultLabels = [
            'VERIFIED' => 'Terverifikasi',
            'FAILED' => 'Gagal',
            'REQUIRES_REVIEW' => 'Perlu Review',
        ];
        $resultColors = [
            'VERIFIED' => 'text-green-700',
            'FAILED' => 'text-red-700',
            'REQUIRES_REVIEW' => 'text-amber-700',
        ];
    @endphp

    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-col gap-1.5">
                <flux:heading size="xl">Hasil Verifikasi Identitas</flux:heading>
                <flux:subheading>{{ $verification->customer?->full_name ?? '-' }}</flux:subheading>
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

            <h2 class="text-lg font-bold text-center mb-6 uppercase tracking-wider">Hasil Verifikasi Identitas</h2>

            <div class="mb-4 text-sm">
                <p>Tanggal Verifikasi: <strong>{{ format_date_indonesian($verification->verification_timestamp, true) }}</strong></p>
            </div>

            <div class="mb-6 rounded-lg p-4 text-center">
                <p class="text-2xl font-extrabold uppercase {{ $resultColors[$verification->result] ?? 'text-neutral-700' }}">
                    {{ $resultLabels[$verification->result] ?? $verification->result }}
                </p>
            </div>

            <div class="text-sm leading-relaxed space-y-3">
                <table class="w-full text-sm my-4">
                    <tr>
                        <td class="py-1 pr-4 w-1/3 text-neutral-500">Nama Nasabah</td>
                        <td class="py-1">: <strong>{{ $verification->customer?->full_name ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Nomor Nasabah</td>
                        <td class="py-1">: <span class="font-mono">{{ $verification->customer?->customer_code ?? '-' }}</span></td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">No. Pinjaman</td>
                        <td class="py-1">: <span class="font-mono">{{ $verification->loan?->loan_number ?? '-' }}</span></td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Metode Verifikasi</td>
                        <td class="py-1">: {{ $methodLabels[$verification->verification_method] ?? $verification->verification_method }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Nama Terverifikasi</td>
                        <td class="py-1">: <strong>{{ $verification->verified_name }}</strong></td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">No. Identitas Terverifikasi</td>
                        <td class="py-1">: <span class="font-mono">{{ $verification->verified_id_number }}</span></td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Petugas Verifikator</td>
                        <td class="py-1">: {{ $verification->verifier?->name ?? '-' }}</td>
                    </tr>
                </table>

                @if ($verification->notes)
                    <p>Catatan: {{ $verification->notes }}</p>
                @endif

                <p>Demikian hasil verifikasi identitas ini dibuat sebagai dokumentasi resmi pada Sistem NADI.</p>
            </div>

            <div class="mt-12 grid grid-cols-2 gap-8 text-sm text-center">
                <div>
                    <p class="font-semibold">Verifikator</p>
                    <div class="mt-20 border-t border-neutral-400 pt-2">
                        <p>{{ $verification->verifier?->name ?? '-' }}</p>
                    </div>
                </div>
                <div>
                    <p class="font-semibold">Disetujui oleh Sistem</p>
                    <div class="mt-20 border-t border-neutral-400 pt-2">
                        <p>{{ $verification->verification_timestamp ? format_date_indonesian($verification->verification_timestamp, true) : '-' }}</p>
                    </div>
                </div>
            </div>
        </flux:card>
    </div>
</x-layouts::app>
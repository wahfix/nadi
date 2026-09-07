<x-layouts::app :title="'Ringkasan Pinjaman '.$loan->loan_number">
    @include('partials.print-app')

    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-col gap-1.5">
                <flux:heading size="xl">Ringkasan Pinjaman</flux:heading>
                <flux:subheading>{{ $loan->loan_number }}</flux:subheading>
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

            <h2 class="text-lg font-bold text-center mb-6 uppercase tracking-wider">Ringkasan Perjanjian Pinjaman</h2>

            <div class="mb-4 text-sm">
                <p>Nomor: <strong class="font-mono">{{ $loan->loan_number }}</strong></p>
                <p>Dicetak: <strong>{{ format_date_indonesian(now(), true) }}</strong></p>
            </div>

            <div class="text-sm leading-relaxed space-y-3">
                <table class="w-full text-sm my-4">
                    <tr>
                        <td class="py-1 pr-4 w-1/3 text-neutral-500">Nama Nasabah</td>
                        <td class="py-1">: <strong>{{ $loan->customer?->full_name ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Nomor Nasabah</td>
                        <td class="py-1">: <span class="font-mono">{{ $loan->customer?->customer_code ?? '-' }}</span></td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Nomor Identitas</td>
                        <td class="py-1">: <span class="font-mono">{{ $loan->customer?->id_number ?? '-' }}</span></td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Alamat</td>
                        <td class="py-1">: {{ $loan->customer?->address ?? '-' }}, {{ $loan->customer?->city ?? '-' }}</td>
                    </tr>
                </table>

                <table class="w-full text-sm my-4">
                    <tr class="border-b border-neutral-300">
                        <td class="py-2 font-semibold" colspan="2">Ketentuan Pinjaman</td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 w-1/3 text-neutral-500">Pokok Pinjaman</td>
                        <td class="py-1">: <strong>{{ format_rupiah($loan->principal_amount) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Suku Bunga</td>
                        <td class="py-1">: {{ format_interest_rate($loan->interest_rate) }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Metode Bunga</td>
                        <td class="py-1">: {{ $loan->interest_method === 'FLAT' ? 'Flat' : 'Reducing Balance (Menurun Efektif)' }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Tenor</td>
                        <td class="py-1">: {{ $loan->tenor }} kali angsuran ({{ $loan->installment_frequency === 'WEEKLY' ? 'mingguan' : 'bulanan' }})</td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Total Bunga</td>
                        <td class="py-1">: {{ format_rupiah($loan->total_interest) }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Total Pembayaran</td>
                        <td class="py-1">: <strong>{{ format_rupiah($loan->total_payable) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Angsuran per Periode</td>
                        <td class="py-1">: <strong>{{ format_rupiah($loan->installment_amount) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Tanggal Pencairan</td>
                        <td class="py-1">: {{ $loan->disbursement_date ? format_date_indonesian($loan->disbursement_date) : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Jatuh Tempo Pertama</td>
                        <td class="py-1">: {{ $loan->first_due_date ? format_date_indonesian($loan->first_due_date) : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="py-1 pr-4 text-neutral-500">Jatuh Tempo Terakhir</td>
                        <td class="py-1">: {{ $loan->maturity_date ? format_date_indonesian($loan->maturity_date) : '-' }}</td>
                    </tr>
                </table>

                @if ($loan->status === 'ACTIVE' || $loan->status === 'OVERDUE')
                    <table class="w-full text-sm my-4">
                        <tr class="border-b border-neutral-300">
                            <td class="py-2 font-semibold" colspan="2">Status Berlaku</td>
                        </tr>
                        <tr>
                            <td class="py-1 pr-4 w-1/3 text-neutral-500">Sisa Pokok</td>
                            <td class="py-1">: {{ format_rupiah($loan->outstanding_principal) }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 pr-4 text-neutral-500">Sisa Bunga</td>
                            <td class="py-1">: {{ format_rupiah($loan->outstanding_interest) }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 pr-4 text-neutral-500">Sisa Denda</td>
                            <td class="py-1">: {{ format_rupiah($loan->outstanding_penalty) }}</td>
                        </tr>
                        <tr>
                            <td class="py-1 pr-4 text-neutral-500">Sisa Tagihan Total</td>
                            <td class="py-1">: <strong>{{ format_rupiah($loan->outstanding_total) }}</strong></td>
                        </tr>
                    </table>
                @endif

                <p>Demikian ringkasan perjanjian pinjaman ini dibuat sebagai dokumentasi resmi pada Sistem NADI.</p>
            </div>

            <div class="mt-12 grid grid-cols-2 gap-8 text-sm text-center">
                <div class="flex flex-col gap-1">
                    <span class="text-neutral-500">Debitur (Nasabah)</span>
                    <span class="mt-16 border-t border-neutral-400 pt-2 font-semibold">{{ $loan->customer?->full_name ?? '-' }}</span>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-neutral-500">Petugas (Credit Officer)</span>
                    <span class="mt-16 border-t border-neutral-400 pt-2 font-semibold">{{ $loan->creator?->name ?? '-' }}</span>
                </div>
            </div>
        </flux:card>
    </div>
</x-layouts::app>
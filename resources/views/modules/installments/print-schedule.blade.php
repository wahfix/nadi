<x-layouts::app :title="'Jadwal Angsuran '.$loan->loan_number">
    @include('partials.print-app')

    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-col gap-1.5">
                <flux:heading size="xl">Jadwal Angsuran</flux:heading>
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

            <h2 class="text-lg font-bold text-center mb-6 uppercase tracking-wider">Jadwal Angsuran</h2>

            <div class="mb-6 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-neutral-500">Nasabah</p>
                    <p class="font-semibold">{{ $loan->customer?->full_name ?? '-' }}</p>
                    <p><span class="font-mono">{{ $loan->customer?->customer_code ?? '-' }}</span> • <span class="font-mono">{{ $loan->customer?->id_number ?? '-' }}</span></p>
                </div>
                <div class="text-right">
                    <p class="text-neutral-500">Pinjaman</p>
                    <p class="font-mono font-semibold">{{ $loan->loan_number }}</p>
                    <p>{{ format_rupiah($loan->principal_amount) }} • {{ $loan->tenor }}x • {{ format_interest_rate($loan->interest_rate) }}</p>
                </div>
            </div>

            @php
                $paidCount = $loan->installments->where('status', 'PAID')->count();
            @endphp

            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-neutral-900 text-left">
                        <th class="py-2 pr-2">Angsuran Ke-</th>
                        <th class="py-2 pr-2">Jatuh Tempo</th>
                        <th class="py-2 pr-2 text-right">Pokok</th>
                        <th class="py-2 pr-2 text-right">Bunga</th>
                        <th class="py-2 pr-2 text-right">Denda</th>
                        <th class="py-2 pr-2 text-right">Total</th>
                        <th class="py-2 pr-2 text-right">Sisa</th>
                        <th class="py-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($loan->installments as $installment)
                        <tr class="border-b border-neutral-200">
                            <td class="py-2 pr-2">Ke-{{ $installment->installment_number }}</td>
                            <td class="py-2 pr-2">{{ format_date_indonesian($installment->due_date) }}</td>
                            <td class="py-2 pr-2 text-right">{{ format_rupiah($installment->principal_amount) }}</td>
                            <td class="py-2 pr-2 text-right">{{ format_rupiah($installment->interest_amount) }}</td>
                            <td class="py-2 pr-2 text-right">{{ format_rupiah($installment->penalty_amount) }}</td>
                            <td class="py-2 pr-2 text-right font-semibold">{{ format_rupiah($installment->total_amount) }}</td>
                            <td class="py-2 pr-2 text-right">{{ format_rupiah($installment->remaining_amount) }}</td>
                            <td class="py-2">
                                {{ ['PENDING' => 'Belum Bayar', 'PARTIALLY_PAID' => 'Sebagian', 'PAID' => 'Lunas', 'OVERDUE' => 'Tunggak', 'WAIVED' => 'Dihapuskan'][$installment->status] ?? $installment->status }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-6 text-center text-neutral-500">Jadwal angsuran belum tersedia.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <p class="mt-4 text-xs text-neutral-500">
                Status angsuran lunas: {{ $paidCount }} dari {{ $loan->installments->count() }} periode.
            </p>
        </flux:card>
    </div>
</x-layouts::app>
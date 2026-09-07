<x-layouts::app :title="__('Detail Angsuran')">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:heading size="xl">Angsuran Ke-{{ $installment->installment_number }}</flux:heading>
            <flux:subheading>
                Pinjaman
                <a href="{{ route('loans.show', $installment->loan) }}" wire:navigate class="font-mono text-neutral-700 underline dark:text-neutral-300">
                    {{ $installment->loan->loan_number }}
                </a>
                —
                {{ $installment->loan->customer?->full_name ?? '-' }}
            </flux:subheading>
        </div>

        <x-flash-message type="success" :message="session('success')" />
        <x-flash-message type="error" :message="session('error')" />

        <flux:card class="grid grid-cols-2 gap-6 sm:grid-cols-4">
            <div>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">Jatuh Tempo</p>
                <p class="mt-1 font-semibold {{ $installment->status === 'OVERDUE' ? 'text-red-600' : '' }}">
                    {{ format_date_indonesian($installment->due_date) }}
                </p>
            </div>
            <div>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">Total Tagihan</p>
                <p class="mt-1 font-semibold">{{ format_rupiah($installment->total_due) }}</p>
            </div>
            <div>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">Terbayar</p>
                <p class="mt-1 font-semibold">{{ format_rupiah($installment->total_paid) }}</p>
            </div>
            <div>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">Sisa Tagihan</p>
                <p class="mt-1 font-semibold {{ $installment->remaining_amount > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                    {{ format_rupiah($installment->remaining_amount) }}
                </p>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Rincian Tagihan</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Komponen</flux:table.column>
                    <flux:table.column>Kewajiban</flux:table.column>
                    <flux:table.column>Terbayar</flux:table.column>
                    <flux:table.column>Sisa</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    <flux:table.row>
                        <flux:table.cell>Pokok</flux:table.cell>
                        <flux:table.cell>{{ format_rupiah($installment->principal_due) }}</flux:table.cell>
                        <flux:table.cell>{{ format_rupiah($installment->principal_paid) }}</flux:table.cell>
                        <flux:table.cell>{{ format_rupiah($installment->principal_due - $installment->principal_paid) }}</flux:table.cell>
                    </flux:table.row>
                    <flux:table.row>
                        <flux:table.cell>Bunga</flux:table.cell>
                        <flux:table.cell>{{ format_rupiah($installment->interest_due) }}</flux:table.cell>
                        <flux:table.cell>{{ format_rupiah($installment->interest_paid) }}</flux:table.cell>
                        <flux:table.cell>{{ format_rupiah($installment->interest_due - $installment->interest_paid) }}</flux:table.cell>
                    </flux:table.row>
                    <flux:table.row>
                        <flux:table.cell>Denda</flux:table.cell>
                        <flux:table.cell>{{ format_rupiah($installment->penalty_due) }}</flux:table.cell>
                        <flux:table.cell>{{ format_rupiah($installment->penalty_paid) }}</flux:table.cell>
                        <flux:table.cell>{{ format_rupiah($installment->penalty_due - $installment->penalty_paid) }}</flux:table.cell>
                    </flux:table.row>
                </flux:table.rows>
            </flux:table>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Pembayaran Terkait</flux:heading>

            @if ($installment->payments->isEmpty())
                <div class="flex flex-col items-center justify-center gap-3 p-8 text-center">
                    <flux:icon name="banknotes" variant="outline" class="size-8 text-neutral-400" />
                    <flux:text>Belum ada pembayaran untuk angsuran ini.</flux:text>
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>No. Pembayaran</flux:table.column>
                        <flux:table.column>Tanggal</flux:table.column>
                        <flux:table.column>Metode</flux:table.column>
                        <flux:table.column>Jumlah</flux:table.column>
                        <flux:table.column>Diterima Oleh</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Kuitansi</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($installment->payments as $payment)
                            <flux:table.row>
                                <flux:table.cell>
                                    <a href="{{ route('payments.show', $payment) }}" wire:navigate class="font-mono text-xs text-neutral-700 hover:underline dark:text-neutral-300">
                                        {{ $payment->payment_number }}
                                    </a>
                                </flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ format_date($payment->payment_date) }}</flux:table.cell>
                                <flux:table.cell>{{ $payment->payment_method }}</flux:table.cell>
                                <flux:table.cell>{{ format_rupiah($payment->amount) }}</flux:table.cell>
                                <flux:table.cell>{{ $payment->receivedBy?->name ?? '-' }}</flux:table.cell>
                                <flux:table.cell>
                                    @if ($payment->reversal)
                                        <flux:badge color="red">Dibatalkan</flux:badge>
                                    @else
                                        <flux:badge color="emerald">Valid</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    @can('payments.receipt')
                                        <a href="{{ route('payments.receipt', $payment) }}" wire:navigate class="text-sm text-neutral-500 hover:underline">
                                            Lihat
                                        </a>
                                    @endcan
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:card>

        <div class="flex items-center justify-end">
            <flux:button as="a" :href="route('loans.show', $installment->loan)" wire:navigate variant="ghost" icon="arrow-left">
                Kembali ke Detail Pinjaman
            </flux:button>
        </div>
    </div>
</x-layouts::app>
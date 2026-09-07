<div class="flex flex-col gap-6">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-stat-card icon="banknotes" label="Penerimaan Kas Hari Ini" :value="format_rupiah($dashboard['today_amount'])" :meta="$dashboard['today_count'].' transaksi'" accent="green" />
        <x-stat-card icon="receipt-percent" label="Rata-rata per Transaksi" :value="format_rupiah($dashboard['today_count'] > 0 ? intdiv($dashboard['today_amount'], $dashboard['today_count']) : 0)" accent="blue" />
    </div>

    @can('payments.create')
        <div class="flex gap-3">
            <flux:button as="a" :href="route('payments.create')" wire:navigate variant="primary" icon="plus">
                Catat Pembayaran
            </flux:button>
        </div>
    @endcan

    <flux:card>
        <flux:heading size="lg">Pembayaran Terbaru</flux:heading>
        <flux:subheading>8 transaksi penerimaan terakhir.</flux:subheading>

        @if ($dashboard['recent']->isEmpty())
            <div class="flex flex-col items-center justify-center gap-3 p-8 text-center">
                <flux:icon name="banknotes" variant="outline" class="size-8 text-neutral-400" />
                <flux:text>Belum ada pembayaran tercatat.</flux:text>
            </div>
        @else
            <flux:table class="mt-4">
                <flux:table.columns>
                    <flux:table.column>No. Pembayaran</flux:table.column>
                    <flux:table.column>Nasabah</flux:table.column>
                    <flux:table.column>Tanggal</flux:table.column>
                    <flux:table.column>Metode</flux:table.column>
                    <flux:table.column>Jumlah</flux:table.column>
                    <flux:table.column>Kuitansi</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($dashboard['recent'] as $payment)
                        <flux:table.row>
                            <flux:table.cell>
                                <a href="{{ route('payments.show', $payment) }}" wire:navigate class="font-mono text-xs hover:underline">{{ $payment->payment_number }}</a>
                            </flux:table.cell>
                            <flux:table.cell>{{ $payment->customer?->full_name ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ format_date($payment->payment_date) }}</flux:table.cell>
                            <flux:table.cell>{{ $payment->payment_method }}</flux:table.cell>
                            <flux:table.cell>{{ format_rupiah($payment->amount) }}</flux:table.cell>
                            <flux:table.cell>
                                @can('payments.receipt')
                                    <a href="{{ route('payments.receipt', $payment) }}" wire:navigate class="text-sm text-neutral-500 hover:underline">Lihat</a>
                                @endcan
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>
</div>
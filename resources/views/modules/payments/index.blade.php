<x-layouts::app :title="__('Pembayaran')">
    @php
        $methodBadges = [
            'CASH' => 'green',
            'BANK_TRANSFER' => 'blue',
            'QRIS' => 'blue',
            'OTHER' => 'neutral',
        ];
        $methodLabels = [
            'CASH' => 'Tunai',
            'BANK_TRANSFER' => 'Transfer Bank',
            'QRIS' => 'QRIS',
            'OTHER' => 'Lainnya',
        ];
    @endphp

    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:heading size="xl">Pembayaran</flux:heading>
            <flux:subheading>Pencatatan setoran kasir, alokasi denda, bunga, pokok, hingga pembalikan.</flux:subheading>
        </div>

        <x-flash-message type="success" :message="session('success')" />
        <x-flash-message type="error" :message="session('error')" />

        @can('payments.create')
            <div class="flex items-center justify-end">
                <flux:button as="a" :href="route('payments.create')" wire:navigate icon="plus">
                    Catat Pembayaran
                </flux:button>
            </div>
        @endcan

        <flux:card>
            <form method="GET" action="{{ route('payments.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
                <div class="min-w-64 flex-1">
                    <flux:input
                        type="search"
                        name="search"
                        :value="request('search')"
                        :placeholder="__('Cari nomor pembayaran, nomor pinjaman, atau nama nasabah...')"
                    />
                </div>

                <flux:select name="payment_method" class="w-44">
                    <flux:select.option value="">Semua Metode</flux:select.option>
                    @foreach ($methodOptions as $code => $label)
                        <flux:select.option :value="$code" :selected="request('payment_method') == $code">
                            {{ $label }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select name="status" class="w-40">
                    <flux:select.option value="">Semua Status</flux:select.option>
                    <flux:select.option value="valid" :selected="request('status') == 'valid'">Sah</flux:select.option>
                    <flux:select.option value="reversed" :selected="request('status') == 'reversed'">Dibalikkan</flux:select.option>
                </flux:select>

                <flux:button variant="primary" type="submit">
                    Filter
                </flux:button>

                @if (request()->hasAny(['search', 'payment_method', 'status']))
                    <flux:button as="a" :href="route('payments.index')" wire:navigate variant="ghost">
                        Reset
                    </flux:button>
                @endif
            </form>

            @if ($payments->isEmpty())
                <div class="flex flex-col items-center justify-center gap-3 p-10 text-center">
                    <flux:icon name="banknotes" variant="outline" class="size-10 text-neutral-400" />
                    <flux:heading size="lg">Belum ada pembayaran.</flux:heading>
                    <flux:text>Catat pembayaran pertama dari setoran kasir untuk memulai alur alokasi otomatis.</flux:text>
                    @can('payments.create')
                        <flux:button as="a" :href="route('payments.create')" wire:navigate class="mt-4">
                            Catat Pembayaran
                        </flux:button>
                    @endcan
                </div>
            @else
                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>No. Pembayaran</flux:table.column>
                            <flux:table.column>Tanggal</flux:table.column>
                            <flux:table.column>Nasabah</flux:table.column>
                            <flux:table.column>Pinjaman</flux:table.column>
                            <flux:table.column>Nominal</flux:table.column>
                            <flux:table.column>Metode</flux:table.column>
                            <flux:table.column>Kasir</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column>Aksi</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($payments as $payment)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <a href="{{ route('payments.show', $payment) }}" wire:navigate class="font-mono text-sm font-medium text-neutral-900 hover:underline">
                                            {{ $payment->payment_number }}
                                        </a>
                                    </flux:table.cell>
                                    <flux:table.cell>{{ format_date($payment->payment_date) }}</flux:table.cell>
                                    <flux:table.cell>
                                        <a href="{{ route('customers.show', $payment->customer) }}" wire:navigate class="font-medium hover:underline">
                                            {{ $payment->customer?->full_name ?? '-' }}
                                        </a>
                                    </flux:table.cell>
                                    <flux:table.cell class="font-mono text-xs text-neutral-600">
                                        {{ $payment->loan?->loan_number }}
                                    </flux:table.cell>
                                    <flux:table.cell>{{ format_rupiah($payment->amount) }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge :color="$methodBadges[$payment->payment_method] ?? 'neutral'">
                                            {{ $methodLabels[$payment->payment_method] ?? $payment->payment_method }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell class="text-sm">{{ $payment->receivedBy?->name ?? '-' }}</flux:table.cell>
                                    <flux:table.cell>
                                        @if ($payment->isReversed())
                                            <flux:badge color="red">Dibalikkan</flux:badge>
                                        @else
                                            <flux:badge color="green">Sah</flux:badge>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:button as="a" :href="route('payments.show', $payment)" wire:navigate size="sm" variant="ghost">
                                            Lihat
                                        </flux:button>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                <div class="mt-4">
                    {{ $payments->links() }}
                </div>
            @endif
        </flux:card>
    </div>
</x-layouts::app>
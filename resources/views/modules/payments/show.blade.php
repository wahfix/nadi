<x-layouts::app :title="$payment->payment_number">
    @php
        $methodLabels = [
            'CASH' => 'Tunai',
            'BANK_TRANSFER' => 'Transfer Bank',
            'QRIS' => 'QRIS',
            'OTHER' => 'Lainnya',
        ];
    @endphp

    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('payments.index')" wire:navigate>Pembayaran</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $payment->payment_number }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        <x-flash-message type="success" :message="session('success')" />
        <x-flash-message type="error" :message="session('error')" />

        <flux:card>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-2">
                        <flux:heading size="xl" class="font-mono">{{ $payment->payment_number }}</flux:heading>
                        @if ($payment->isReversed())
                            <flux:badge color="red">Dibalikkan</flux:badge>
                        @else
                            <flux:badge color="green">Sah</flux:badge>
                        @endif
                    </div>
                    <flux:text class="text-neutral-500">
                        Tanggal:
                        <span class="font-medium text-neutral-800">{{ format_date_indonesian($payment->payment_date, true) }}</span>
                        · Diterima oleh:
                        <span class="font-medium text-neutral-800">{{ $payment->receivedBy?->name ?? '-' }}</span>
                    </flux:text>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @can('receipt', $payment)
                        <flux:button as="a" :href="route('payments.receipt', $payment)" target="_blank" variant="primary" icon="printer">
                            Cetak Kuitansi
                        </flux:button>
                    @endcan

                    @can('reverse', $payment)
                        @if (! $payment->isReversed())
                            <flux:button variant="danger" type="button" icon="arrow-uturn-left"
                                         onclick="document.getElementById('reverse-payment-modal').showModal()">
                                Balikkan Pembayaran
                            </flux:button>
                        @endif
                    @endcan
                </div>
            </div>
        </flux:card>

        <div class="grid gap-4 lg:grid-cols-2">
            <flux:card>
                <flux:heading size="lg">Detail Transaksi</flux:heading>
                <div class="mt-5 grid gap-x-8 gap-y-4 md:grid-cols-2">
                    <div class="flex flex-col gap-1">
                        <flux:text variant="small" class="text-neutral-500">Nominal Dibayar</flux:text>
                        <flux:text class="text-lg font-semibold text-neutral-900 dark:text-white">{{ format_rupiah($payment->amount) }}</flux:text>
                    </div>
                    <div class="flex flex-col gap-1">
                        <flux:text variant="small" class="text-neutral-500">Metode Pembayaran</flux:text>
                        <flux:text class="font-medium">{{ $methodLabels[$payment->payment_method] ?? $payment->payment_method }}</flux:text>
                    </div>
                    <div class="flex flex-col gap-1">
                        <flux:text variant="small" class="text-neutral-500">Nomor Referensi</flux:text>
                        <flux:text class="font-medium">{{ $payment->reference_number ?: '-' }}</flux:text>
                    </div>
                    <div class="flex flex-col gap-1">
                        <flux:text variant="small" class="text-neutral-500">Pinjaman Terkait</flux:text>
                        <flux:text class="font-medium">
                            <a href="{{ route('loans.show', $payment->loan) }}" wire:navigate class="font-mono hover:underline">
                                {{ $payment->loan?->loan_number ?? '-' }}
                            </a>
                        </flux:text>
                    </div>
                    <div class="flex flex-col gap-1">
                        <flux:text variant="small" class="text-neutral-500">Nasabah</flux:text>
                        <flux:text class="font-medium">
                            <a href="{{ route('customers.show', $payment->customer) }}" wire:navigate class="hover:underline">
                                {{ $payment->customer?->full_name ?? '-' }}
                            </a>
                        </flux:text>
                    </div>
                    <div class="flex flex-col gap-1">
                        <flux:text variant="small" class="text-neutral-500">Petugas Kasir</flux:text>
                        <flux:text class="font-medium">{{ $payment->receivedBy?->name ?? '-' }}</flux:text>
                    </div>
                    @if ($payment->notes)
                        <div class="flex flex-col gap-1 md:col-span-2">
                            <flux:text variant="small" class="text-neutral-500">Catatan</flux:text>
                            <flux:text>{{ $payment->notes }}</flux:text>
                        </div>
                    @endif
                </div>
            </flux:card>

            <flux:card>
                <flux:heading size="lg">Rincian Alokasi Pembayaran</flux:heading>
                <p class="mt-1 text-xs text-neutral-500">
                    Terkunci saat pembayaran disimpan sesuai urutan wajib: Denda → Bunga → Pokok.
                </p>
                <div class="mt-5 flex flex-col gap-4">
                    @php
                        $breakdown = [
                            ['label' => 'Pokok Pinjaman', 'value' => $payment->principal_component, 'color' => 'bg-neutral-900'],
                            ['label' => 'Bunga', 'value' => $payment->interest_component, 'color' => 'bg-blue-500'],
                            ['label' => 'Denda', 'value' => $payment->penalty_component, 'color' => 'bg-red-500'],
                        ];
                    @endphp

                    <div class="flex h-4 w-full overflow-hidden rounded-full border border-neutral-200 dark:border-neutral-700">
                        @foreach ($breakdown as $item)
                            @if ($item['value'] > 0)
                                <div
                                    class="{{ $item['color'] }}"
                                    style="width: {{ $payment->amount > 0 ? round(($item['value'] / $payment->amount) * 100, 2) : 0 }}%"
                                    title="{{ $item['label'] }}"
                                ></div>
                            @endif
                        @endforeach
                    </div>

                    <div class="overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-700">
                        @foreach ($breakdown as $item)
                            <div class="flex items-center justify-between border-b border-neutral-100 px-4 py-3 text-sm last:border-0 dark:border-neutral-800">
                                <div class="flex items-center gap-3">
                                    <span class="size-2.5 rounded-full {{ $item['color'] }}"></span>
                                    <span class="text-neutral-700 dark:text-neutral-200">{{ $item['label'] }}</span>
                                </div>
                                <span class="font-medium">{{ format_rupiah($item['value']) }}</span>
                            </div>
                        @endforeach
                        <div class="flex items-center justify-between bg-neutral-50 px-4 py-3 text-sm font-semibold dark:bg-neutral-800/50">
                            <span>Total Dibayar</span>
                            <span>{{ format_rupiah($payment->amount) }}</span>
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>

        @if ($payment->isReversed())
            <flux:card>
                <div class="flex flex-col gap-4">
                    <div class="flex items-center gap-2">
                        <flux:icon name="arrow-uturn-left" variant="outline" class="size-5 text-red-500" />
                        <flux:heading size="lg">Pembalikan Pembayaran</flux:heading>
                    </div>
                    <div class="grid gap-x-8 gap-y-4 md:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Dibalikkan Oleh</flux:text>
                            <flux:text class="font-medium">{{ $payment->reversal?->reversedBy?->name ?? '-' }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Waktu Pembalikan</flux:text>
                            <flux:text class="font-medium">{{ format_date_indonesian($payment->reversal?->reversed_at, true) }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1 md:col-span-2">
                            <flux:text variant="small" class="text-neutral-500">Alasan</flux:text>
                            <flux:text>{{ $payment->reversal?->reason }}</flux:text>
                        </div>
                    </div>
                </div>
            </flux:card>
        @endif

        {{-- Modal: Balikkan Pembayaran --}}
        <dialog id="reverse-payment-modal" class="m-auto rounded-2xl bg-white p-0 shadow-2xl dark:bg-zinc-800">
            <div class="flex min-w-96 max-w-lg flex-col gap-4 p-6">
                <flux:heading size="lg">Konfirmasi Pembalikan Pembayaran</flux:heading>
                <p class="text-sm text-neutral-600 dark:text-neutral-300">
                    Pembalikan atas <strong class="font-mono">{{ $payment->payment_number }}</strong> sebesar
                    {{ format_rupiah($payment->amount) }} akan mengembalikan sisa tagihan pinjaman
                    <strong class="font-mono">{{ $payment->loan?->loan_number }}</strong> ke posisi semula secara
                    otomatis. Alasan wajib diisi untuk jejak audit.
                </p>
                <form method="POST" action="{{ route('payments.reverse', $payment) }}" class="flex flex-col gap-4">
                    @csrf
                    <flux:textarea name="reason" label="Alasan Pembalikan" rows="3" required
                                   placeholder="Contoh: setoran ganda, nominal salah, nasabah membatalkan"></flux:textarea>
                    @error('reason')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <div class="flex items-center justify-end gap-3">
                        <flux:button variant="ghost" type="button" onclick="document.getElementById('reverse-payment-modal').close()">
                            Batal
                        </flux:button>
                        <flux:button variant="danger" type="submit">
                            Ya, Balikkan
                        </flux:button>
                    </div>
                </form>
            </div>
        </dialog>
    </div>
</x-layouts::app>
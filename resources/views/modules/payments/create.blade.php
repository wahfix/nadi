<x-layouts::app :title="__('Catat Pembayaran')">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('payments.index')" wire:navigate>Pembayaran</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Catat Pembayaran</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        <x-flash-message type="success" :message="session('success')" />
        <x-flash-message type="error" :message="session('error')" />

        @php
            $loansData = $loans->map(fn ($loan) => [
                'id' => $loan->id,
                'loan_number' => $loan->loan_number,
                'customer' => $loan->customer?->full_name ?? '-',
                'principal' => $loan->outstanding_principal,
                'interest' => $loan->outstanding_interest,
                'penalty' => $loan->outstanding_penalty,
                'total' => $loan->outstanding_total,
                'overdue' => $loan->status === 'OVERDUE',
            ])->values();
        @endphp

        <div x-data="{
            loans: {{ Illuminate\Support\Js::from($loansData) }},
            loanId: {{ $preselectedLoan?->id ?? 'null' }},
            rupiah(value) {
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
            },
        }" @searchable-select:change="loanId = $event.detail.value">
            <flux:card>
                <flux:heading size="lg">Tanda Terima Setoran Nasabah</flux:heading>

                <form method="POST" action="{{ route('payments.store') }}" class="mt-5 flex flex-col gap-5">
                    @csrf

                    <x-searchable-select
                        name="loan_id"
                        id="loan_id"
                        label="Pinjaman"
                        required
                        :selected="$preselectedLoan?->id ? (string) $preselectedLoan->id : ''"
                        placeholder="Ketik nomor pinjaman (NADI-LOAN-…) atau nama nasabah…"
                        :options="$loans->map(fn ($loan) => [
                            'id' => (string) $loan->id,
                            'label' => $loan->loan_number,
                            'sublabel' => $loan->customer?->full_name . ' · Sisa ' . format_rupiah($loan->outstanding_total),
                            'badge' => $loan->status === 'OVERDUE' ? 'Menunggak' : '',
                            'search' => trim(($loan->customer?->full_name ?? '') . ' ' . $loan->loan_number),
                        ])->values()"
                    />
                    @error('loan_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <template x-if="loanId">
                        <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
                            <template x-for="loan in loans" :key="loan.id">
                                <div x-show="String(loanId) === String(loan.id)">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <p class="font-mono text-sm font-semibold text-neutral-900 dark:text-white" x-text="loan.loan_number"></p>
                                            <p class="text-sm text-neutral-500" x-text="loan.customer"></p>
                                        </div>
                                        <flux:badge x-show="loan.overdue" color="amber">Menunggak</flux:badge>
                                    </div>
                                    <div class="mt-3 grid grid-cols-2 gap-3 text-sm md:grid-cols-4">
                                        <div class="flex flex-col">
                                            <span class="text-neutral-500">Sisa Pokok</span>
                                            <span class="font-medium" x-text="rupiah(loan.principal)"></span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-neutral-500">Sisa Bunga</span>
                                            <span class="font-medium" x-text="rupiah(loan.interest)"></span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-neutral-500">Sisa Denda</span>
                                            <span class="font-medium" x-text="rupiah(loan.penalty)"></span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-neutral-500">Sisa Total</span>
                                            <span class="font-semibold text-neutral-900 dark:text-white" x-text="rupiah(loan.total)"></span>
                                        </div>
                                    </div>
                                    <p class="mt-3 text-xs text-neutral-500">
                                        Alokasi otomatis sesuai urutan wajib: denda terlebih dahulu, kemudian bunga, dan terakhir pokok pinjaman.
                                    </p>
                                </div>
                            </template>
                        </div>
                    </template>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="amount" class="mb-1.5 block text-sm font-medium text-neutral-700 dark:text-neutral-200">
                                Nominal Dibayar (Rp)
                            </label>
                            <flux:input id="amount" name="amount" type="number" min="1" step="1" inputmode="numeric"
                                        value="{{ old('amount') }}" placeholder="contoh: 1100000" required />
                            @error('amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="payment_date" class="mb-1.5 block text-sm font-medium text-neutral-700 dark:text-neutral-200">
                                Tanggal Pembayaran
                            </label>
                            <flux:input id="payment_date" name="payment_date" type="date" value="{{ old('payment_date', now()->toDateString()) }}" required />
                            @error('payment_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="payment_method" class="mb-1.5 block text-sm font-medium text-neutral-700 dark:text-neutral-200">
                                Metode Pembayaran
                            </label>
                            <select
                                name="payment_method"
                                id="payment_method"
                                required
                                class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-200 dark:border-neutral-600 dark:bg-neutral-800"
                            >
                                <option value="">— Pilih metode —</option>
                                @foreach ($methodOptions as $code => $label)
                                    <option value="{{ $code }}" :selected="false" {{ old('payment_method') == $code ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('payment_method')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="reference_number" class="mb-1.5 block text-sm font-medium text-neutral-700 dark:text-neutral-200">
                                Nomor Referensi <span class="text-neutral-400">(opsional, untuk transfer/QRIS)</span>
                            </label>
                            <flux:input id="reference_number" name="reference_number" type="text" maxlength="255"
                                        value="{{ old('reference_number') }}" placeholder="contoh: ID TRX / nomor bukti" />
                            @error('reference_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="mb-1.5 block text-sm font-medium text-neutral-700 dark:text-neutral-200">
                            Catatan <span class="text-neutral-400">(opsional)</span>
                        </label>
                        <flux:textarea id="notes" name="notes" rows="2" maxlength="1000"
                                       placeholder="Catatan tambahan untuk transaksi ini">{{ old('notes') }}</flux:textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-neutral-200 pt-5 dark:border-neutral-700">
                        <flux:button as="a" :href="route('payments.index')" wire:navigate variant="ghost">
                            Batal
                        </flux:button>
                        <flux:button variant="primary" type="submit" icon="check">
                            Simpan Pembayaran
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        </div>
    </div>
</x-layouts::app>
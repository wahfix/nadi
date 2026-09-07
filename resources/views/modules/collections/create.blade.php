<x-layouts::app :title="__('Catat Aktivitas Penagihan')">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('collections.index')" wire:navigate>Penagihan</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Catat Aktivitas</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        <x-flash-message type="success" :message="session('success')" />
        <x-flash-message type="error" :message="session('error')" />

        @php
            $loansData = $loans->map(fn ($loan) => [
                'id' => $loan->id,
                'loan_number' => $loan->loan_number,
                'customer' => $loan->customer?->full_name ?? '-',
                'total' => $loan->outstanding_total,
                'status' => $loan->status,
            ])->values();
        @endphp

        <div
            x-data="{
                loans: {{ Illuminate\Support\Js::from($loansData) }},
                loanId: {{ $preselectedLoan?->id ?? 'null' }},
                result: 'NO_RESPONSE',
                rupiah(value) {
                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                },
            }"
        >
            <flux:card>
                <flux:heading size="lg">Laporan Kontak Penagihan</flux:heading>

                <form method="POST" action="{{ route('collections.store') }}" class="mt-5 flex flex-col gap-5">
                    @csrf

                    <div>
                        <label for="loan_id" class="mb-1.5 block text-sm font-medium text-neutral-700 dark:text-neutral-200">
                            Pinjaman yang Ditagih
                        </label>
                        <select
                            name="loan_id"
                            id="loan_id"
                            required
                            x-model.number="loanId"
                            class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-200 dark:border-neutral-600 dark:bg-neutral-800"
                        >
                            <option value="">— Pilih pinjaman yang ditagih —</option>
                            @foreach ($loans as $loan)
                                <option value="{{ $loan->id }}" :selected="loanId == {{ $loan->id }}">
                                    {{ $loan->loan_number }} · {{ $loan->customer?->full_name }}
                                    (sisa {{ format_rupiah($loan->outstanding_total) }})
                                </option>
                            @endforeach
                        </select>
                        @error('loan_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <template x-if="loanId">
                        <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
                            <template x-for="loan in loans" :key="loan.id">
                                <div x-show="String(loanId) === String(loan.id)">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <p class="font-mono text-sm font-semibold text-neutral-900 dark:text-white" x-text="loan.loan_number"></p>
                                            <p class="text-sm text-neutral-500" x-text="loan.customer"></p>
                                        </div>
                                        <flux:badge x-show="loan.status === 'OVERDUE'" color="amber">Menunggak</flux:badge>
                                    </div>
                                    <p class="mt-3 text-sm text-neutral-500">
                                        Sisa tagihan berjalan:
                                        <span class="font-semibold text-neutral-900 dark:text-white" x-text="rupiah(loan.total)"></span>
                                    </p>
                                </div>
                            </template>
                        </div>
                    </template>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="contact_date" class="mb-1.5 block text-sm font-medium text-neutral-700 dark:text-neutral-200">
                                Waktu Kontak
                            </label>
                            <flux:input id="contact_date" name="contact_date" type="datetime-local"
                                        value="{{ old('contact_date', now()->format('Y-m-d\TH:i')) }}" required />
                            @error('contact_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="contact_method" class="mb-1.5 block text-sm font-medium text-neutral-700 dark:text-neutral-200">
                                Metode Kontak
                            </label>
                            <select
                                name="contact_method"
                                id="contact_method"
                                required
                                class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-200 dark:border-neutral-600 dark:bg-neutral-800"
                            >
                                <option value="">— Pilih metode —</option>
                                @foreach ($methodOptions as $code => $label)
                                    <option value="{{ $code }}" {{ old('contact_method') == $code ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('contact_method')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <span class="mb-1.5 block text-sm font-medium text-neutral-700 dark:text-neutral-200">Hasil Penagihan</span>
                        <div class="grid gap-2 sm:grid-cols-3">
                            @foreach ($resultOptions as $code => $label)
                                <label
                                    class="flex cursor-pointer items-center gap-2 rounded-lg border border-neutral-200 p-3 text-sm transition-colors has-checked:border-neutral-900 has-checked:bg-neutral-900 has-checked:text-white"
                                >
                                    <input
                                        type="radio"
                                        name="result"
                                        value="{{ $code }}"
                                        x-model="result"
                                        class="size-4 accent-neutral-900"
                                        {{ old('result', 'NO_RESPONSE') == $code ? 'checked' : '' }}
                                    />
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        @error('result')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <template x-if="result === 'PROMISE_TO_PAY'">
                        <div class="grid gap-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 md:grid-cols-2 dark:border-emerald-800 dark:bg-emerald-900/20">
                            <div>
                                <label for="promise_to_pay_date" class="mb-1.5 block text-sm font-medium text-neutral-700 dark:text-neutral-200">
                                    Tanggal Janji Bayar
                                </label>
                                <flux:input id="promise_to_pay_date" name="promise_to_pay_date" type="date"
                                            :value="old('promise_to_pay_date')" />
                                @error('promise_to_pay_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="promise_to_pay_amount" class="mb-1.5 block text-sm font-medium text-neutral-700 dark:text-neutral-200">
                                    Nominal Janji Bayar (Rp)
                                </label>
                                <flux:input id="promise_to_pay_amount" name="promise_to_pay_amount" type="number" min="1" step="1"
                                            inputmode="numeric" :value="old('promise_to_pay_amount')" placeholder="contoh: 500000" />
                                @error('promise_to_pay_amount')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </template>

                    <div>
                        <label for="notes" class="mb-1.5 block text-sm font-medium text-neutral-700 dark:text-neutral-200">
                            Catatan <span class="text-neutral-400">(opsional)</span>
                        </label>
                        <flux:textarea id="notes" name="notes" rows="3" maxlength="1000"
                                       placeholder="Ringkasan hasil pembicaraan atau kondisi di lapangan">{{ old('notes') }}</flux:textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-neutral-200 pt-5 dark:border-neutral-700">
                        <flux:button as="a" :href="route('collections.index')" wire:navigate variant="ghost">
                            Batal
                        </flux:button>
                        <flux:button variant="primary" type="submit" icon="check">
                            Simpan Aktivitas
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        </div>
    </div>
</x-layouts::app>
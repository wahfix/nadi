@php
    $loan ??= null;
    $method ??= 'POST';
    $submitLabel ??= 'Simpan Pengajuan';
    $methodLabels = [
        'FLAT' => 'Flat (Bunga Tetap)',
        'REDUCING_BALANCE' => 'Efektif Menurun (Anuitas)',
    ];
    $frequencyLabels = [
        'MONTHLY' => 'Bulanan',
        'WEEKLY' => 'Mingguan',
    ];
    $tenorOptions = [1, 2, 3, 6, 12, 18, 24];
@endphp

<flux:heading size="lg">Data Pengajuan</flux:heading>

<form
    method="POST"
    action="{{ $action }}"
    class="flex flex-col gap-6"
    x-ref="loanForm"
>
    @csrf
    @method($method)

    <div class="grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
            <flux:field>
                <flux:select
                    name="customer_id"
                    :label="__('Nasabah')"
                    required
                    x-model="customerId"
                    x-on:change="triggerPreview()"
                >
                    <flux:select.option value="">-- Pilih Nasabah --</flux:select.option>
                    @foreach ($customers as $customer)
                        <flux:select.option :value="$customer->id" :selected="old('customer_id', $loan?->customer_id) == $customer->id">
                            {{ $customer->full_name }} — {{ $customer->customer_code }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>
        </div>

        <flux:field>
            <flux:input
                name="principal_amount"
                :label="__('Pokok Pinjaman (Rp)')"
                :value="old('principal_amount', $loan?->principal_amount)"
                required
                inputmode="numeric"
                x-model="principal"
                x-on:input="triggerPreview()"
                :placeholder="__('Contoh: 10000000')"
            />
        </flux:field>

        <flux:field>
            <flux:input
                name="interest_rate"
                :label="__('Suku Bunga (%) per periode')"
                :value="old('interest_rate', $loan ? $loan->interest_rate / 100 : '2')"
                required
                inputmode="decimal"
                x-model="interestRate"
                x-on:input="triggerPreview()"
                :placeholder="__('Contoh: 2')"
            />
        </flux:field>

        <flux:field>
            <flux:select name="interest_method" :label="__('Metode Bunga')" required x-model="method" x-on:change="triggerPreview()">
                @foreach ($methodLabels as $value => $label)
                    <flux:select.option :value="$value" :selected="old('interest_method', $loan?->interest_method ?? 'FLAT') == $value">
                        {{ $label }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:select name="tenor" :label="__('Tenor (jumlah periode)')" required x-model="tenor" x-on:change="triggerPreview()">
                <flux:select.option value="">-- Pilih Tenor --</flux:select.option>
                @foreach ($tenorOptions as $tenorOption)
                    <flux:select.option :value="$tenorOption" :selected="old('tenor', $loan?->tenor) == $tenorOption">
                        {{ $tenorOption }} periode
                    </flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:select name="installment_frequency" :label="__('Frekuensi Angsuran')" required x-model="frequency" x-on:change="triggerPreview()">
                @foreach ($frequencyLabels as $value => $label)
                    <flux:select.option :value="$value" :selected="old('installment_frequency', $loan?->installment_frequency ?? 'MONTHLY') == $value">
                        {{ $label }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:input
                name="disbursement_date"
                :label="__('Tanggal Pencairan (opsional)')"
                type="date"
                :value="old('disbursement_date', $loan?->disbursement_date?->format('Y-m-d'))"
                x-model="disbursementDate"
                x-on:change="triggerPreview()"
            />
        </flux:field>

        <flux:field>
            <flux:input
                name="first_due_date"
                :label="__('Tanggal Jatuh Tempo Pertama')"
                type="date"
                :value="old('first_due_date', $loan?->first_due_date?->format('Y-m-d'))"
                required
                x-model="firstDueDate"
                x-on:change="triggerPreview()"
            />
        </flux:field>
    </div>

    <div class="flex items-center justify-end gap-3 pt-2">
        <flux:button as="a" :href="route('loans.index')" wire:navigate variant="ghost">
            Batal
        </flux:button>
        <flux:button variant="primary" type="button" x-on:click="$refs.confirmModal.showModal()">
            {{ $submitLabel }}
        </flux:button>
    </div>

    {{-- Modal konfirmasi pengajuan --}}
    <dialog x-ref="confirmModal" class="m-auto rounded-2xl bg-white p-0 shadow-2xl dark:bg-zinc-800">
        <div class="flex min-w-96 max-w-lg flex-col gap-4 p-6">
            <div>
                <flux:heading size="lg">Konfirmasi Pengajuan Pinjaman</flux:heading>
                <flux:text class="text-sm">Periksa kembali rincian sebelum pengajuan disimpan.</flux:text>
            </div>

            <div class="flex flex-col gap-2 rounded-xl bg-neutral-50 p-4 text-sm dark:bg-zinc-700/50">
                <template x-if="selectedCustomerName">
                    <div class="flex justify-between"><span class="text-neutral-500">Nasabah</span><span class="font-medium" x-text="selectedCustomerName"></span></div>
                </template>
                <div class="flex justify-between"><span class="text-neutral-500">Pokok Pinjaman</span><span class="font-medium" x-text="previewPrincipal"></span></div>
                <div class="flex justify-between"><span class="text-neutral-500">Suku Bunga</span><span class="font-medium" x-text="previewInterest"></span></div>
                <div class="flex justify-between"><span class="text-neutral-500">Metode Bunga</span><span class="font-medium" x-text="previewMethod"></span></div>
                <div class="flex justify-between"><span class="text-neutral-500">Tenor</span><span class="font-medium" x-text="previewTenor"></span></div>
                <div class="flex justify-between"><span class="text-neutral-500">Total Bunga</span><span class="font-medium" x-text="previewTotalInterest"></span></div>
                <div class="flex justify-between"><span class="text-neutral-500">Total Kewajiban</span><span class="font-medium" x-text="previewTotalPayable"></span></div>
                <div class="flex justify-between"><span class="text-neutral-500">Angsuran per Periode</span><span class="font-medium" x-text="previewInstallment"></span></div>
                <div class="flex justify-between"><span class="text-neutral-500">Jatuh Tempo Akhir</span><span class="font-medium" x-text="previewMaturity"></span></div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <flux:button variant="ghost" type="button" x-on:click="$refs.confirmModal.close()">
                    Batal
                </flux:button>
                <flux:button variant="primary" type="button" x-on:click="$refs.loanForm.submit()">
                    Ya, {{ $method === 'PUT' ? 'Simpan Perubahan' : 'Simpan Pengajuan' }}
                </flux:button>
            </div>
        </div>
    </dialog>
</form>
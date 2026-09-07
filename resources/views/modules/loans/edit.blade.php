<x-layouts::app :title="__('Ubah Pengajuan Pinjaman')">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('loans.index')" wire:navigate>Pinjaman</flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route('loans.show', $loan)" wire:navigate>{{ $loan->loan_number }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Ubah Pengajuan</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl">Ubah Pengajuan Pinjaman</flux:heading>
            <flux:subheading>Perbarui pengajuan yang masih berstatus Draft.</flux:subheading>
        </div>

        <x-flash-message type="error" :message="$errors->any() ? 'Terdapat kesalahan pada pengisian form. Periksa kembali kolom yang disorot.' : null" />

        <div
            class="grid gap-6 lg:grid-cols-3"
            x-data="loansPreview({
                customerId: '{{ old('customer_id', $loan->customer_id) }}',
                principal: '{{ old('principal_amount', $loan->principal_amount) }}',
                interestRate: '{{ old('interest_rate', $loan->interest_rate / 100) }}',
                method: '{{ old('interest_method', $loan->interest_method) }}',
                tenor: '{{ old('tenor', $loan->tenor) }}',
                frequency: '{{ old('installment_frequency', $loan->installment_frequency) }}',
                disbursementDate: '{{ old('disbursement_date', $loan->disbursement_date?->format('Y-m-d')) }}',
                firstDueDate: '{{ old('first_due_date', $loan->first_due_date?->format('Y-m-d')) }}',
            })"
        >
            <div class="lg:col-span-2">
                <flux:card>
                    @include('modules.loans._form', [
                        'loan' => $loan,
                        'action' => route('loans.update', $loan),
                        'method' => 'PUT',
                        'submitLabel' => 'Simpan Perubahan',
                        'customers' => $customers,
                    ])
                </flux:card>
            </div>

            <div>
                @include('modules.loans._preview')
            </div>
        </div>
    </div>

    @include('modules.loans._scripts')
</x-layouts::app>
<x-layouts::app :title="__('Ajukan Pinjaman')">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('loans.index')" wire:navigate>Pinjaman</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Ajukan Pinjaman</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl">Ajukan Pinjaman</flux:heading>
            <flux:subheading>Buat pengajuan pinjaman baru. Pratinjau kalkulasi diperbarui otomatis.</flux:subheading>
        </div>

        <x-flash-message type="error" :message="$errors->any() ? 'Terdapat kesalahan pada pengisian form. Periksa kembali kolom yang disorot.' : null" />

        <div
            class="grid gap-6 lg:grid-cols-3"
            x-data="loansPreview({
                customerId: '{{ old('customer_id') }}',
                principal: '{{ old('principal_amount') }}',
                interestRate: '{{ old('interest_rate', '2') }}',
                method: '{{ old('interest_method', 'FLAT') }}',
                tenor: '{{ old('tenor') }}',
                frequency: '{{ old('installment_frequency', 'MONTHLY') }}',
                disbursementDate: '{{ old('disbursement_date') }}',
                firstDueDate: '{{ old('first_due_date') }}',
            })"
        >
            <div class="lg:col-span-2">
                <flux:card>
                    @include('modules.loans._form', [
                        'action' => route('loans.store'),
                        'method' => 'POST',
                        'submitLabel' => 'Simpan Pengajuan',
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
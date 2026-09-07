<x-layouts::app :title="__('Ubah Nasabah')">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('customers.index')" wire:navigate>Nasabah</flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route('customers.show', $customer)" wire:navigate>{{ $customer->full_name }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Ubah Nasabah</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl">Ubah Nasabah</flux:heading>
            <flux:subheading>Perbarui profil dan data pekerjaan debiturnya.</flux:subheading>
        </div>

        <x-flash-message type="error" :message="$errors->any() ? 'Terdapat kesalahan pada pengisian form. Periksa kembali kolom yang disorot.' : null" />

        <flux:card class="max-w-4xl">
            @include('modules.customers._form', [
                'customer' => $customer,
                'action' => route('customers.update', $customer),
                'method' => 'PUT',
                'submitLabel' => 'Simpan Perubahan',
            ])
        </flux:card>
    </div>
</x-layouts::app>
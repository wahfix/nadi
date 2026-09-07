<x-layouts::app :title="__('Tambah Nasabah')">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('customers.index')" wire:navigate>Nasabah</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Tambah Nasabah</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl">Tambah Nasabah</flux:heading>
            <flux:subheading>Daftarkan profil debitur baru beserta data pekerjaannya.</flux:subheading>
        </div>

        <x-flash-message type="error" :message="$errors->any() ? 'Terdapat kesalahan pada pengisian form. Periksa kembali kolom yang disorot.' : null" />

        <flux:card class="max-w-4xl">
            @include('modules.customers._form', [
                'action' => route('customers.store'),
                'method' => 'POST',
                'submitLabel' => 'Simpan Nasabah',
            ])
        </flux:card>
    </div>
</x-layouts::app>
<x-layouts::app :title="__('Tambah Pengguna')">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('users.index')" wire:navigate>Pengguna</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Tambah Pengguna</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl">Tambah Pengguna</flux:heading>
            <flux:subheading>Buat akun staf baru dan tetapkan peran aksesnya.</flux:subheading>
        </div>

        <x-flash-message type="error" :message="$errors->any() ? 'Terdapat kesalahan pada pengisian form. Periksa kembali kolom yang disorot.' : null" />

        <flux:card class="max-w-2xl">
            <form method="POST" action="{{ route('users.store') }}" class="flex flex-col gap-5">
                @csrf

                <flux:field>
                    <flux:input
                        name="name"
                        :label="__('Nama Lengkap')"
                        :value="old('name')"
                        required
                        autofocus
                        :placeholder="__('Nama staf')"
                    />
                </flux:field>

                <flux:field>
                    <flux:input
                        name="email"
                        :label="__('Alamat Email')"
                        :value="old('email')"
                        type="email"
                        required
                        :placeholder="__('nama@contoh.test')"
                    />
                </flux:field>

                <flux:field>
                    <flux:input
                        name="password"
                        :label="__('Kata Sandi')"
                        type="password"
                        required
                        viewable
                        :placeholder="__('Minimal 8 karakter')"
                    />
                </flux:field>

                <flux:field>
                    <flux:select name="role_id" :label="__('Peran Akses')" required>
                        <flux:select.option value="">-- Pilih peran --</flux:select.option>
                        @foreach ($roles as $role)
                            <flux:select.option :value="$role->id" :selected="old('role_id') == $role->id">
                                {{ $role->display_name }} ({{ $role->name }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <flux:button as="a" :href="route('users.index')" wire:navigate variant="ghost">
                        Batal
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        Simpan Pengguna
                    </flux:button>
                </div>
            </form>
        </flux:card>
    </div>
</x-layouts::app>
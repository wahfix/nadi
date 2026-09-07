<x-layouts::app :title="__('Pengguna')">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:heading size="xl">Pengguna</flux:heading>
            <flux:subheading>Kelola akun staf dan peran akses sistem.</flux:subheading>
        </div>

        <x-flash-message type="success" :message="session('success')" />
        <x-flash-message type="error" :message="session('error')" />

        @can('users.manage')
            <div class="flex items-center justify-end">
                <flux:button as="a" :href="route('users.create')" wire:navigate icon="plus">
                    Tambah Pengguna
                </flux:button>
            </div>
        @endcan

        <flux:card>
            @if ($users->isEmpty())
                <div class="flex flex-col items-center justify-center gap-3 p-10 text-center">
                    <flux:icon name="user-group" variant="outline" class="size-10 text-neutral-400" />
                    <flux:heading size="lg">Belum ada pengguna.</flux:heading>
                    <flux:text>Tambahkan akun staf pertama untuk mulai menggunakan sistem.</flux:text>
                    @can('users.manage')
                        <flux:button as="a" :href="route('users.create')" wire:navigate class="mt-4">
                            Tambah Pengguna
                        </flux:button>
                    @endcan
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Nama</flux:table.column>
                        <flux:table.column>Alamat Email</flux:table.column>
                        <flux:table.column>Peran</flux:table.column>
                        <flux:table.column>Terdaftar</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($users as $user)
                            <flux:table.row>
                                <flux:table.cell>
                                    <div class="flex items-center gap-3">
                                        <flux:avatar :name="$user->name" :initials="$user->initials()" size="sm" />
                                        <span class="font-medium">{{ $user->name }}</span>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>{{ $user->email }}</flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse ($user->roles as $role)
                                            <flux:badge color="neutral">{{ $role->display_name }}</flux:badge>
                                        @empty
                                            <flux:badge color="red">Tanpa Peran</flux:badge>
                                        @endforelse
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>{{ $user->created_at ? format_date_indonesian($user->created_at) : '-' }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <div class="mt-4">
                    {{ $users->links() }}
                </div>
            @endif
        </flux:card>
    </div>
</x-layouts::app>
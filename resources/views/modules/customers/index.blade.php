<x-layouts::app :title="__('Nasabah')">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:heading size="xl">Nasabah</flux:heading>
            <flux:subheading>Kelola profil, kontak, dan riwayat pekerjaan debitur.</flux:subheading>
        </div>

        <x-flash-message type="success" :message="session('success')" />
        <x-flash-message type="error" :message="session('error')" />

        @can('customers.create')
            <div class="flex items-center justify-end">
                <flux:button as="a" :href="route('customers.create')" wire:navigate icon="plus">
                    Tambah Nasabah
                </flux:button>
            </div>
        @endcan

        <flux:card>
            <form method="GET" action="{{ route('customers.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
                <div class="min-w-64 flex-1">
                    <flux:input
                        type="search"
                        name="search"
                        :value="request('search')"
                        :placeholder="__('Cari kode, nama, telepon, atau NIK...')"
                    />
                </div>

                <flux:select name="status" class="w-44">
                    <flux:select.option value="">Semua Status</flux:select.option>
                    @foreach ($statusOptions as $status)
                        <flux:select.option :value="$status" :selected="request('status') == $status">
                            {{ $status }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select name="city" class="w-44">
                    <flux:select.option value="">Semua Kota</flux:select.option>
                    @foreach ($cities as $city)
                        <flux:select.option :value="$city" :selected="request('city') == $city">
                            {{ $city }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:button variant="primary" type="submit">
                    Filter
                </flux:button>

                @if (request()->hasAny(['search', 'status', 'city']))
                    <flux:button as="a" :href="route('customers.index')" wire:navigate variant="ghost">
                        Reset
                    </flux:button>
                @endif
            </form>

            @if ($customers->isEmpty())
                <div class="flex flex-col items-center justify-center gap-3 p-10 text-center">
                    <flux:icon name="user-group" variant="outline" class="size-10 text-neutral-400" />
                    <flux:heading size="lg">Belum ada nasabah.</flux:heading>
                    <flux:text>Daftarkan nasabah pertama untuk mulai membangun profil debitur.</flux:text>
                    @can('customers.create')
                        <flux:button as="a" :href="route('customers.create')" wire:navigate class="mt-4">
                            Tambah Nasabah
                        </flux:button>
                    @endcan
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Kode</flux:table.column>
                        <flux:table.column>Nama</flux:table.column>
                        <flux:table.column>NIK</flux:table.column>
                        <flux:table.column>Perusahaan</flux:table.column>
                        <flux:table.column>Telepon</flux:table.column>
                        <flux:table.column>Kota</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Aksi</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($customers as $customer)
                            <flux:table.row>
                                <flux:table.cell>
                                    <span class="font-mono text-sm">{{ $customer->customer_code }}</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <a href="{{ route('customers.show', $customer) }}" wire:navigate class="font-medium text-neutral-900 hover:underline">
                                        {{ $customer->full_name }}
                                    </a>
                                </flux:table.cell>
                                <flux:table.cell>{{ $customer->nik_masked }}</flux:table.cell>
                                <flux:table.cell>{{ $customer->activeEmployment?->company_name ?? '-' }}</flux:table.cell>
                                <flux:table.cell>{{ $customer->phone }}</flux:table.cell>
                                <flux:table.cell>{{ $customer->city }}</flux:table.cell>
                                <flux:table.cell>
                                    @if ($customer->status === 'ACTIVE')
                                        <flux:badge color="green">Aktif</flux:badge>
                                    @elseif ($customer->status === 'BLOCKED')
                                        <flux:badge color="red">Diblokir</flux:badge>
                                    @else
                                        <flux:badge color="neutral">Nonaktif</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex items-center gap-2">
                                        <flux:button as="a" :href="route('customers.show', $customer)" wire:navigate size="sm" variant="ghost">
                                            Lihat
                                        </flux:button>
                                        @can('customers.edit')
                                            <flux:button as="a" :href="route('customers.edit', $customer)" wire:navigate size="sm" variant="ghost">
                                                Ubah
                                            </flux:button>
                                        @endcan
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <div class="mt-4">
                    {{ $customers->links() }}
                </div>
            @endif
        </flux:card>
    </div>
</x-layouts::app>
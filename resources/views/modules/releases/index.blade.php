<x-layouts::app :title="__('Pengambilan Jaminan')">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:heading size="xl">Pengambilan Jaminan</flux:heading>
            <flux:subheading>Riwayat penyerahan agunan jaminan kepada pemohon yang berhak.</flux:subheading>
        </div>

        <x-flash-message type="success" :message="session('success')" />
        <x-flash-message type="error" :message="session('error')" />

        @can('releases.execute')
            <div class="flex items-center justify-end">
                <flux:button as="a" :href="route('releases.create')" wire:navigate icon="plus">
                    Proses Pengambilan
                </flux:button>
            </div>
        @endcan

        <flux:card>
            <form method="GET" action="{{ route('releases.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
                <div class="min-w-64 flex-1">
                    <flux:input
                        type="search"
                        name="search"
                        :value="request('search')"
                        :placeholder="__('Cari nomor rilis, nama penerima, atau nama nasabah...')"
                    />
                </div>

                <flux:button variant="primary" type="submit">Filter</flux:button>

                @if (request()->has('search'))
                    <flux:button as="a" :href="route('releases.index')" wire:navigate variant="ghost">Reset</flux:button>
                @endif
            </form>

            @if ($releases->isEmpty())
                <div class="flex flex-col items-center justify-center gap-3 p-10 text-center">
                    <flux:icon name="check-badge" variant="outline" class="size-10 text-neutral-400" />
                    <flux:heading size="lg">Belum ada pengambilan jaminan.</flux:heading>
                    <flux:text>Proses pengambilan pertama setelah jaminan memenuhi seluruh syarat.</flux:text>
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Nomor Rilis</flux:table.column>
                        <flux:table.column>Tanggal</flux:table.column>
                        <flux:table.column>Nasabah</flux:table.column>
                        <flux:table.column>Jaminan</flux:table.column>
                        <flux:table.column>Diterima Oleh</flux:table.column>
                        <flux:table.column>Aksi</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($releases as $release)
                            <flux:table.row>
                                <flux:table.cell>
                                    <span class="font-mono text-sm">{{ $release->release_number }}</span>
                                </flux:table.cell>
                                <flux:table.cell>{{ format_date($release->release_date) }}</flux:table.cell>
                                <flux:table.cell>
                                    <a href="{{ route('customers.show', $release->customer) }}" wire:navigate class="font-medium text-neutral-900 hover:underline">
                                        {{ $release->customer?->full_name ?? '-' }}
                                    </a>
                                </flux:table.cell>
                                <flux:table.cell class="font-mono text-xs">{{ $release->collateral?->collateral_code ?? '-' }}</flux:table.cell>
                                <flux:table.cell>{{ $release->released_to_name }}</flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex items-center gap-2">
                                        <flux:button as="a" :href="route('releases.show', $release)" wire:navigate size="sm" variant="ghost">
                                            Lihat
                                        </flux:button>
                                        <flux:button as="a" :href="route('releases.receipt', $release)" wire:navigate size="sm" variant="ghost" icon="printer">
                                            Cetak
                                        </flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <div class="mt-4">
                    {{ $releases->links() }}
                </div>
            @endif
        </flux:card>
    </div>
</x-layouts::app>

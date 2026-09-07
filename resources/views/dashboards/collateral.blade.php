<div class="flex flex-col gap-6">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-stat-card icon="lock-closed" label="Dalam Penyimpanan" :value="$dashboard['in_custody_count']" :meta="format_rupiah($dashboard['in_custody_value'])" accent="blue" />
        <x-stat-card icon="check-badge" label="Siap Diserahkan" :value="$dashboard['ready_count']" :meta="format_rupiah($dashboard['ready_value'])" accent="green" />
    </div>

    @can('collaterals.receive')
        <div class="flex gap-3">
            <flux:button as="a" :href="route('collaterals.create')" wire:navigate variant="primary" icon="plus">
                Terima Jaminan
            </flux:button>
        </div>
    @endcan

    <flux:card>
        <flux:heading size="lg">Serah Terima Jaminan Terakhir</flux:heading>
        <flux:subheading>5 berita acara serah terima terbaru.</flux:subheading>

        @if ($dashboard['recent']->isEmpty())
            <div class="flex flex-col items-center justify-center gap-3 p-8 text-center">
                <flux:icon name="wallet" variant="outline" class="size-8 text-neutral-400" />
                <flux:text>Belum ada serah terima jaminan.</flux:text>
            </div>
        @else
            <flux:table class="mt-4">
                <flux:table.columns>
                    <flux:table.column>No. Serah Terima</flux:table.column>
                    <flux:table.column>Nasabah</flux:table.column>
                    <flux:table.column>Agunan</flux:table.column>
                    <flux:table.column>Diterima Oleh</flux:table.column>
                    <flux:table.column>Tanggal</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($dashboard['recent'] as $release)
                        <flux:table.row>
                            <flux:table.cell>
                                <a href="{{ route('releases.show', $release) }}" wire:navigate class="font-mono text-xs hover:underline">{{ $release->release_number }}</a>
                            </flux:table.cell>
                            <flux:table.cell>{{ $release->customer?->full_name ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $release->collateral?->collateral_code ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $release->released_to_name }}</flux:table.cell>
                            <flux:table.cell>{{ format_date($release->release_date) }}</flux:table.cell>
                            <flux:table.cell>
                                <a href="{{ route('releases.receipt', $release) }}" wire:navigate class="text-sm text-neutral-500 hover:underline">Cetak</a>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>
</div>
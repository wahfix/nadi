<x-layouts::app :title="$release->release_number">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('releases.index')" wire:navigate>Pengambilan Jaminan</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $release->release_number }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        <x-flash-message type="success" :message="session('success')" />

        <flux:card>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-3">
                        <flux:heading size="xl">{{ $release->release_number }}</flux:heading>
                        <flux:badge color="emerald">Selesai</flux:badge>
                    </div>
                    <flux:text class="text-sm text-neutral-500">
                        {{ format_date_indonesian($release->created_at, true) }}
                    </flux:text>
                </div>
                <flux:button as="a" :href="route('releases.receipt', $release)" wire:navigate icon="printer" variant="primary">
                    Cetak Berita Acara
                </flux:button>
            </div>
        </flux:card>

        <div class="grid gap-6 xl:grid-cols-2">
            <flux:card>
                <flux:heading size="lg">Data Penyerahan</flux:heading>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="flex flex-col gap-1">
                        <flux:text variant="small" class="text-neutral-500">Nomor Rilis</flux:text>
                        <flux:text class="font-mono font-semibold">{{ $release->release_number }}</flux:text>
                    </div>
                    <div class="flex flex-col gap-1">
                        <flux:text variant="small" class="text-neutral-500">Tanggal Penyerahan</flux:text>
                        <flux:text>{{ format_date($release->release_date) }}</flux:text>
                    </div>
                    <div class="flex flex-col gap-1">
                        <flux:text variant="small" class="text-neutral-500">Diterima Oleh</flux:text>
                        <flux:text class="font-semibold">{{ $release->released_to_name }}</flux:text>
                    </div>
                    <div class="flex flex-col gap-1">
                        <flux:text variant="small" class="text-neutral-500">Hubungan dengan Nasabah</flux:text>
                        <flux:text>{{ $release->relationship_to_customer }}</flux:text>
                    </div>
                    <div class="flex flex-col gap-1">
                        <flux:text variant="small" class="text-neutral-500">Lokasi Penyerahan</flux:text>
                        <flux:text>{{ $release->release_location }}</flux:text>
                    </div>
                    <div class="flex flex-col gap-1">
                        <flux:text variant="small" class="text-neutral-500">Petugas Penyerah</flux:text>
                        <flux:text>{{ $release->releasedBy?->name ?? '-' }}</flux:text>
                    </div>
                    @if ($release->witness)
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Saksi</flux:text>
                            <flux:text>{{ $release->witness->name }}</flux:text>
                        </div>
                    @endif
                    @if ($release->handover_notes)
                        <div class="flex flex-col gap-1 md:col-span-2">
                            <flux:text variant="small" class="text-neutral-500">Catatan</flux:text>
                            <flux:text>{{ $release->handover_notes }}</flux:text>
                        </div>
                    @endif
                </div>
            </flux:card>

            <flux:card>
                <flux:heading size="lg">Data Jaminan</flux:heading>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="flex flex-col gap-1">
                        <flux:text variant="small" class="text-neutral-500">Kode Jaminan</flux:text>
                        <flux:text class="font-mono">{{ $release->collateral?->collateral_code ?? '-' }}</flux:text>
                    </div>
                    <div class="flex flex-col gap-1">
                        <flux:text variant="small" class="text-neutral-500">Jenis</flux:text>
                        <flux:text>{{ $release->collateral?->collateral_type ?? '-' }}</flux:text>
                    </div>
                    <div class="flex flex-col gap-1 md:col-span-2">
                        <flux:text variant="small" class="text-neutral-500">Deskripsi</flux:text>
                        <flux:text>{{ $release->collateral?->description ?? '-' }}</flux:text>
                    </div>
                    <div class="flex flex-col gap-1">
                        <flux:text variant="small" class="text-neutral-500">Nasabah</flux:text>
                        <flux:text>
                            <a href="{{ route('customers.show', $release->customer) }}" wire:navigate class="font-medium hover:underline">
                                {{ $release->customer?->full_name ?? '-' }}
                            </a>
                        </flux:text>
                    </div>
                    <div class="flex flex-col gap-1">
                        <flux:text variant="small" class="text-neutral-500">Pinjaman</flux:text>
                        <flux:text class="font-mono text-sm">{{ $release->loan?->loan_number ?? '-' }}</flux:text>
                    </div>
                    <div class="flex flex-col gap-1">
                        <flux:text variant="small" class="text-neutral-500">Verifikasi Identitas</flux:text>
                        <flux:text class="text-sm">#{{ $release->verified_identity_id }} — {{ $release->identityVerification?->result ?? '-' }}</flux:text>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>
</x-layouts::app>

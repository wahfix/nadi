<x-layouts::app :title="$collateral->collateral_code">
    @php
        $statusColors = [
            'PENDING' => 'neutral',
            'RECEIVED' => 'blue',
            'IN_CUSTODY' => 'green',
            'READY_FOR_RELEASE' => 'amber',
            'RELEASED' => 'emerald',
            'DISPUTED' => 'red',
        ];
        $canUpdateCustody = in_array($collateral->custody_status, ['RECEIVED', 'IN_CUSTODY', 'READY_FOR_RELEASE']);
    @endphp

    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('collaterals.index')" wire:navigate>Jaminan</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $collateral->collateral_code }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        <x-flash-message type="success" :message="session('success')" />
        <x-flash-message type="error" :message="session('error')" />

        <flux:card>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-3">
                        <flux:heading size="xl">{{ $collateral->collateral_code }}</flux:heading>
                        <flux:badge :color="$statusColors[$collateral->custody_status] ?? 'neutral'">
                            {{ $statusLabels[$collateral->custody_status] ?? $collateral->custody_status }}
                        </flux:badge>
                    </div>
                    <flux:text class="text-sm text-neutral-500">
                        {{ $typeLabels[$collateral->collateral_type] ?? $collateral->collateral_type }} &middot; {{ format_rupiah($collateral->estimated_value) }}
                    </flux:text>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <flux:button as="a" :href="route('collaterals.receipt', $collateral)" icon="printer">
                        Surat Tanda Terima
                    </flux:button>
                </div>
            </div>
        </flux:card>

        <div class="grid gap-6 xl:grid-cols-3">
            {{-- Detail Jaminan --}}
            <div class="xl:col-span-2 flex flex-col gap-4">
                <flux:card>
                    <flux:heading size="lg">Detail Jaminan</flux:heading>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Kode Jaminan</flux:text>
                            <flux:text class="font-mono">{{ $collateral->collateral_code }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Nasabah</flux:text>
                            <flux:text>
                                <a href="{{ route('customers.show', $collateral->customer) }}" wire:navigate class="font-medium hover:underline">
                                    {{ $collateral->customer?->full_name ?? '-' }}
                                </a>
                            </flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Nomor Pinjaman</flux:text>
                            <flux:text class="font-mono text-sm">
                                <a href="{{ route('loans.show', $collateral->loan) }}" wire:navigate class="hover:underline">
                                    {{ $collateral->loan?->loan_number ?? '-' }}
                                </a>
                            </flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Jenis Agunan</flux:text>
                            <flux:text>{{ $typeLabels[$collateral->collateral_type] ?? $collateral->collateral_type }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1 md:col-span-2">
                            <flux:text variant="small" class="text-neutral-500">Deskripsi</flux:text>
                            <flux:text>{{ $collateral->description }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Nomor Identifikasi</flux:text>
                            <flux:text class="font-mono">{{ $collateral->identification_number }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Nilai Taksasi</flux:text>
                            <flux:text class="font-semibold">{{ format_rupiah($collateral->estimated_value) }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Tanggal Diterima</flux:text>
                            <flux:text>{{ format_date($collateral->received_date) }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Petugas Penerima</flux:text>
                            <flux:text>{{ $collateral->receivedBy?->name ?? '-' }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1 md:col-span-2">
                            <flux:text variant="small" class="text-neutral-500">Kondisi Fisik</flux:text>
                            <flux:text>{{ $collateral->condition_on_receipt }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Lokasi Penyimpanan</flux:text>
                            <flux:text>{{ $collateral->storage_location }}</flux:text>
                        </div>
                        @if ($collateral->released_at)
                            <div class="flex flex-col gap-1">
                                <flux:text variant="small" class="text-neutral-500">Tanggal Diserahkan</flux:text>
                                <flux:text>{{ format_date_indonesian($collateral->released_at) }}</flux:text>
                            </div>
                        @endif
                    </div>
                </flux:card>

                {{-- Serah Terima (jika sudah ada) --}}
                @if ($collateral->release)
                    <flux:card>
                        <flux:heading size="lg">Berita Acara Serah Terima</flux:heading>
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div class="flex flex-col gap-1">
                                <flux:text variant="small" class="text-neutral-500">Nomor Rilis</flux:text>
                                <flux:text class="font-mono">{{ $collateral->release->release_number }}</flux:text>
                            </div>
                            <div class="flex flex-col gap-1">
                                <flux:text variant="small" class="text-neutral-500">Diterima Oleh</flux:text>
                                <flux:text>{{ $collateral->release->released_to_name }}</flux:text>
                            </div>
                            <div class="flex flex-col gap-1">
                                <flux:text variant="small" class="text-neutral-500">Hubungan</flux:text>
                                <flux:text>{{ $collateral->release->relationship_to_customer }}</flux:text>
                            </div>
                            <div class="flex flex-col gap-1">
                                <flux:text variant="small" class="text-neutral-500">Lokasi</flux:text>
                                <flux:text>{{ $collateral->release->release_location }}</flux:text>
                            </div>
                        </div>
                        <div class="mt-4">
                            <flux:button as="a" :href="route('releases.receipt', $collateral->release)" wire:navigate size="sm" icon="printer">
                                Cetak Berita Acara
                            </flux:button>
                        </div>
                    </flux:card>
                @endif
            </div>

            {{-- Sidebar: Update Status --}}
            <div class="flex flex-col gap-4">
                @if ($canUpdateCustody)
                    <flux:card>
                        <flux:heading size="lg">Ubah Status Penyimpanan</flux:heading>
                        <form method="POST" action="{{ route('collaterals.update-custody', $collateral) }}" class="mt-4 flex flex-col gap-4">
                            @csrf
                            <select name="custody_status" class="flux-input" required>
                                <option value="">— Pilih Status —</option>
                                @if ($collateral->custody_status === 'RECEIVED')
                                    <option value="IN_CUSTODY">Disimpan (IN_CUSTODY)</option>
                                @elseif ($collateral->custody_status === 'IN_CUSTODY')
                                    <option value="READY_FOR_RELEASE">Siap Diserahkan (READY_FOR_RELEASE)</option>
                                    <option value="DISPUTED">Sengketa (DISPUTED)</option>
                                @elseif ($collateral->custody_status === 'READY_FOR_RELEASE')
                                    <option value="IN_CUSTODY">Kembali Disimpan (IN_CUSTODY)</option>
                                @endif
                            </select>
                            <flux:button type="submit" variant="primary" size="sm" icon="check">
                                Perbarui Status
                            </flux:button>
                        </form>
                    </flux:card>
                @endif

                @if ($collateral->custody_status === 'READY_FOR_RELEASE')
                    <flux:card>
                        <flux:heading size="lg">Pengambilan Jaminan</flux:text>
                        <flux:text variant="small" class="mt-2 text-neutral-500">Jaminan ini sudah siap untuk diserahkan kepada pemohon.</flux:text>
                        <flux:button as="a" :href="route('releases.create', ['collateral' => $collateral->id])" wire:navigate class="mt-4" variant="primary" size="sm" icon="check-badge">
                            Proses Pengambilan
                        </flux:button>
                    </flux:card>
                @endif

                <flux:card>
                    <flux:heading size="lg">Riwayat</flux:heading>
                    <div class="mt-4 flex flex-col gap-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-neutral-500">Dibuat</span>
                            <span>{{ format_date_indonesian($collateral->created_at, true) }}</span>
                        </div>
                        @if ($collateral->updated_at && $collateral->updated_at != $collateral->created_at)
                            <div class="flex justify-between">
                                <span class="text-neutral-500">Diperbarui</span>
                                <span>{{ format_date_indonesian($collateral->updated_at, true) }}</span>
                            </div>
                        @endif
                    </div>
                </flux:card>
            </div>
        </div>
    </div>
</x-layouts::app>

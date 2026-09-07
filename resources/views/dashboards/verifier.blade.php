<div class="flex flex-col gap-6">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card icon="inbox-stack" label="Antrean Perlu Review" :value="$dashboard['pending']" :meta="'Menunggu verifikasi pengambilan jaminan'" accent="amber" />
        <x-stat-card icon="check-circle" label="Verifikasi Berhasil" :value="$dashboard['verified']" accent="green" />
        <x-stat-card icon="x-circle" label="Verifikasi Gagal" :value="$dashboard['failed']" accent="red" />
    </div>

    @can('verifications.create')
        <div class="flex gap-3">
            <flux:button as="a" :href="route('verifications.create')" wire:navigate variant="primary" icon="shield-check">
                Verifikasi Identitas Baru
            </flux:button>
        </div>
    @endcan

    <flux:card>
        <flux:heading size="lg">Riwayat Verifikasi Terbaru</flux:heading>
        <flux:subheading>5 pemeriksaan identitas terakhir.</flux:subheading>

        @if ($dashboard['recent']->isEmpty())
            <div class="flex flex-col items-center justify-center gap-3 p-8 text-center">
                <flux:icon name="shield-check" variant="outline" class="size-8 text-neutral-400" />
                <flux:text>Belum ada verifikasi identitas tercatat.</flux:text>
            </div>
        @else
            <flux:table class="mt-4">
                <flux:table.columns>
                    <flux:table.column>Nasabah</flux:table.column>
                    <flux:table.column>Metode</flux:table.column>
                    <flux:table.column>Nama Terverifikasi</flux:table.column>
                    <flux:table.column>Hasil</flux:table.column>
                    <flux:table.column>Waktu</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($dashboard['recent'] as $verification)
                        <flux:table.row>
                            <flux:table.cell>{{ $verification->customer?->full_name ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $verification->verification_method }}</flux:table.cell>
                            <flux:table.cell>{{ $verification->verified_name }}</flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $colors = ['VERIFIED' => 'emerald', 'FAILED' => 'red', 'REQUIRES_REVIEW' => 'amber'];
                                @endphp
                                <flux:badge :color="$colors[$verification->result] ?? 'neutral'">{{ $verification->result }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ format_date_indonesian($verification->verification_timestamp, true) }}</flux:table.cell>
                            <flux:table.cell>
                                <a href="{{ route('verifications.show', $verification) }}" wire:navigate class="text-sm text-neutral-500 hover:underline">Lihat</a>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>
</div>
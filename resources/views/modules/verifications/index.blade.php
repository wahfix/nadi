<x-layouts::app :title="__('Verifikasi Identitas')">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:heading size="xl">Verifikasi Identitas</flux:heading>
            <flux:subheading>Kelola verifikasi identitas pemohon pengambilan jaminan.</flux:subheading>
        </div>

        <x-flash-message type="success" :message="session('success')" />
        <x-flash-message type="error" :message="session('error')" />

        @can('verifications.create')
            <div class="flex items-center justify-end">
                <flux:button as="a" :href="route('verifications.create')" wire:navigate icon="plus">
                    Buat Verifikasi
                </flux:button>
            </div>
        @endcan

        <flux:card>
            <form method="GET" action="{{ route('verifications.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
                <div class="min-w-64 flex-1">
                    <flux:input
                        type="search"
                        name="search"
                        :value="request('search')"
                        :placeholder="__('Cari nama pemohon, nomor identitas, atau nama nasabah...')"
                    />
                </div>

                <flux:select name="result" class="w-48">
                    <flux:select.option value="">Semua Hasil</flux:select.option>
                    @foreach ($resultOptions as $value => $label)
                        <flux:select.option :value="$value" :selected="request('result') == $value">
                            {{ $label }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:button variant="primary" type="submit">Filter</flux:button>

                @if (request()->hasAny(['search', 'result']))
                    <flux:button as="a" :href="route('verifications.index')" wire:navigate variant="ghost">Reset</flux:button>
                @endif
            </form>

            @if ($verifications->isEmpty())
                <div class="flex flex-col items-center justify-center gap-3 p-10 text-center">
                    <flux:icon name="shield-check" variant="outline" class="size-10 text-neutral-400" />
                    <flux:heading size="lg">Belum ada verifikasi identitas.</flux:heading>
                    <flux:text>Buat verifikasi identitas pertama untuk mendukung alur pengambilan jaminan.</flux:text>
                    @can('verifications.create')
                        <flux:button as="a" :href="route('verifications.create')" wire:navigate class="mt-4">
                            Buat Verifikasi
                        </flux:button>
                    @endcan
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Waktu</flux:table.column>
                        <flux:table.column>Nama Pemohon</flux:table.column>
                        <flux:table.column>NIK</flux:table.column>
                        <flux:table.column>Nasabah</flux:table.column>
                        <flux:table.column>Metode</flux:table.column>
                        <flux:table.column>Hasil</flux:table.column>
                        <flux:table.column>Aksi</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($verifications as $verification)
                            @php
                                $resultColors = [
                                    'VERIFIED' => 'green',
                                    'FAILED' => 'red',
                                    'REQUIRES_REVIEW' => 'amber',
                                ];
                            @endphp
                            <flux:table.row>
                                <flux:table.cell>{{ format_date_indonesian($verification->created_at, true) }}</flux:table.cell>
                                <flux:table.cell class="font-medium">{{ $verification->verified_name }}</flux:table.cell>
                                <flux:table.cell class="font-mono text-xs">{{ $verification->verified_id_number }}</flux:table.cell>
                                <flux:table.cell>{{ $verification->customer?->full_name ?? '-' }}</flux:table.cell>
                                <flux:table.cell class="text-xs">{{ $verification->verification_method }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge :color="$resultColors[$verification->result] ?? 'neutral'">
                                        {{ $resultOptions[$verification->result] ?? $verification->result }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:button as="a" :href="route('verifications.show', $verification)" wire:navigate size="sm" variant="ghost">
                                        Lihat
                                    </flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <div class="mt-4">
                    {{ $verifications->links() }}
                </div>
            @endif
        </flux:card>
    </div>
</x-layouts::app>

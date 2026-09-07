<x-layouts::app :title="__('Proses Pengambilan Jaminan')">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('releases.index')" wire:navigate>Pengambilan Jaminan</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Proses Pengambilan</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl">Proses Pengambilan Jaminan</flux:heading>
            <flux:subheading>Validasi 8 syarat mutlak dan serah terima jaminan kepada pemohon.</flux:subheading>
        </div>

        <x-flash-message type="success" :message="session('success')" />
        <x-flash-message type="error" :message="session('error')" />

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <flux:card>
            <form method="GET" action="{{ route('releases.create') }}" class="mb-6 flex flex-wrap items-end gap-3">
                <div class="min-w-64 flex-1">
                    <label class="text-sm font-medium text-neutral-700">Pilih Jaminan yang Akan Diambil</label>
                    <x-searchable-select
                        name="collateral"
                        id="collateral_select"
                        :auto-submit="true"
                        :selected="$preselectedCollateral?->id ? (string) $preselectedCollateral->id : ''"
                        placeholder="Ketik kode jaminan (COL-…) atau nama nasabah…"
                        :options="$collaterals->map(fn ($col) => [
                            'id' => (string) $col->id,
                            'label' => $col->collateral_code,
                            'sublabel' => ($col->customer?->full_name ?? '') . ' · ' . $col->description,
                            'search' => trim(($col->customer?->full_name ?? '') . ' ' . $col->collateral_code . ' ' . $col->description),
                        ])->values()"
                    />
                </div>
            </form>

            @if ($preselectedCollateral && $checklist)
                {{-- Checklist 8 Syarat --}}
                <flux:heading size="lg">Daftar Periksa (Checklist) 8 Syarat</flux:heading>

                <div class="mt-4 flex flex-col gap-2">
                    @foreach ($checklist as $item)
                        <div class="flex items-center gap-3 rounded-lg border px-4 py-2.5 {{ $item['passed'] ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' }}">
                            @if ($item['passed'])
                                <flux:icon name="check-circle" class="size-5 shrink-0 text-emerald-600" />
                            @else
                                <flux:icon name="x-circle" class="size-5 shrink-0 text-red-600" />
                            @endif
                            <div class="flex-1">
                                <span class="text-sm font-medium {{ $item['passed'] ? 'text-emerald-800' : 'text-red-800' }}">{{ $item['label'] }}</span>
                                <span class="ml-2 text-xs {{ $item['passed'] ? 'text-emerald-600' : 'text-red-600' }}">— {{ $item['detail'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                @php $allPassed = collect($checklist)->every('passed'); @endphp

                <div class="mt-4 rounded-lg border px-4 py-3 {{ $allPassed ? 'border-emerald-300 bg-emerald-100' : 'border-red-300 bg-red-100' }}">
                    <span class="text-sm font-bold {{ $allPassed ? 'text-emerald-800' : 'text-red-800' }}">
                        STATUS: {{ $allPassed ? 'JAMINAN SIAP DISERAHKAN' : 'JAMINAN TIDAK DAPAT DISERAHKAN' }}
                    </span>
                </div>

                @if ($allPassed)
                    <hr class="my-4 border-neutral-200" />

                    {{-- Form Serah Terima --}}
                    <flux:heading size="lg">Data Serah Terima</flux:heading>

                    <form method="POST" action="{{ route('releases.store') }}" class="mt-4 flex flex-col gap-4">
                        @csrf
                        <input type="hidden" name="collateral_id" value="{{ $preselectedCollateral->id }}" />
                        <input type="hidden" name="identity_verification_id" value="{{ $verifications->first()?->id }}" />

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-medium text-neutral-700">Nama Penerima *</label>
                                <input type="text" name="released_to_name" value="{{ old('released_to_name') }}" class="flux-input" required />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-medium text-neutral-700">Hubungan dengan Nasabah *</label>
                                <input type="text" name="relationship_to_customer" value="{{ old('relationship_to_customer') }}" class="flux-input" placeholder="Suami, Istri, Anak, dll." required />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-medium text-neutral-700">Tanggal Penyerahan *</label>
                                <input type="date" name="release_date" value="{{ old('release_date', now()->toDateString()) }}" class="flux-input" max="{{ now()->toDateString() }}" required />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-medium text-neutral-700">Lokasi Penyerahan *</label>
                                <input type="text" name="release_location" value="{{ old('release_location') }}" class="flux-input" required />
                            </div>
                            <div class="flex flex-col gap-1 md:col-span-2">
                                <label class="text-sm font-medium text-neutral-700">Catatan Serah Terima</label>
                                <textarea name="handover_notes" rows="2" class="flux-input">{{ old('handover_notes') }}</textarea>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <flux:button as="a" :href="route('releases.index')" wire:navigate variant="ghost">Batal</flux:button>
                            <flux:button type="submit" variant="primary" icon="check">
                                Konfirmasi Penyerahan Jaminan
                            </flux:button>
                        </div>
                    </form>
                @else
                    <div class="mt-4 flex items-center justify-center p-6 text-center">
                        <flux:text class="text-sm text-neutral-500">
                            Tombol penyerahan dinonaktifkan karena tidak semua syarat terpenuhi.
                        </flux:text>
                    </div>
                @endif
            @else
                <div class="flex flex-col items-center justify-center gap-3 p-10 text-center">
                    <flux:icon name="check-badge" variant="outline" class="size-10 text-neutral-400" />
                    <flux:heading size="lg">Pilih jaminan terlebih dahulu.</flux:heading>
                    <flux:text>Pilih jaminan dari daftar di atas untuk memulai proses pengambilan.</flux:text>
                </div>
            @endif
        </flux:card>
    </div>
</x-layouts::app>

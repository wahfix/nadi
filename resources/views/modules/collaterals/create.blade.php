<x-layouts::app :title="__('Terima Jaminan')">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('collaterals.index')" wire:navigate>Jaminan</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Terima Jaminan</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl">Terima Jaminan</flux:heading>
            <flux:subheading>Catat penerimaan fisik agunan jaminan dari nasabah terkait pinjaman tertentu.</flux:subheading>
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
            <form method="POST" action="{{ route('collaterals.store') }}" class="flex flex-col gap-6">
                @csrf

                <div class="flex flex-col gap-1">
                    <flux:heading size="lg">1. Pilih Pinjaman</flux:heading>
                    <flux:text variant="small" class="text-neutral-500">Pilih nomor pinjaman terkait jaminan ini.</flux:text>

                    <select name="loan_id" id="loan_id" class="flux-input mt-2 w-full" required>
                        <option value="">— Pilih Pinjaman —</option>
                        @foreach ($loans as $loan)
                            <option
                                value="{{ $loan->id }}"
                                {{ (old('loan_id', $preselectedLoan?->id) == $loan->id) ? 'selected' : '' }}
                            >
                                {{ $loan->loan_number }} — {{ $loan->customer?->full_name }} ({{ format_rupiah($loan->outstanding_total) }} sisa)
                            </option>
                        @endforeach
                    </select>
                </div>

                <hr class="border-neutral-200" />

                <div class="flex flex-col gap-1">
                    <flux:heading size="lg">2. Data Agunan</flux:heading>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <label for="collateral_type" class="text-sm font-medium text-neutral-700">Jenis Agunan *</label>
                            <select name="collateral_type" id="collateral_type" class="flux-input" required>
                                <option value="">— Pilih Jenis —</option>
                                @foreach ($typeOptions as $value => $label)
                                    <option value="{{ $value }}" {{ old('collateral_type') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label for="identification_number" class="text-sm font-medium text-neutral-700">Nomor Identifikasi *</label>
                            <input type="text" name="identification_number" id="identification_number" value="{{ old('identification_number') }}" class="flux-input" placeholder="BPKB, sertifikat, nomor polisi, serial number..." required />
                        </div>

                        <div class="flex flex-col gap-1 md:col-span-2">
                            <label for="description" class="text-sm font-medium text-neutral-700">Deskripsi Agunan *</label>
                            <textarea name="description" id="description" rows="3" class="flux-input" placeholder="Merek, tipe, tahun, warna, rincian fisik..." required>{{ old('description') }}</textarea>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label for="estimated_value" class="text-sm font-medium text-neutral-700">Nilai Taksasi (Rupiah) *</label>
                            <input type="number" name="estimated_value" id="estimated_value" value="{{ old('estimated_value') }}" class="flux-input" min="1" placeholder="Contoh: 5000000" required />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label for="received_date" class="text-sm font-medium text-neutral-700">Tanggal Penerimaan *</label>
                            <input type="date" name="received_date" id="received_date" value="{{ old('received_date', now()->toDateString()) }}" class="flux-input" max="{{ now()->toDateString() }}" required />
                        </div>

                        <div class="flex flex-col gap-1 md:col-span-2">
                            <label for="condition_on_receipt" class="text-sm font-medium text-neutral-700">Kondisi Fisik Saat Diterima *</label>
                            <textarea name="condition_on_receipt" id="condition_on_receipt" rows="2" class="flux-input" placeholder="Kondisi fisik aset saat diterima..." required>{{ old('condition_on_receipt') }}</textarea>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label for="storage_location" class="text-sm font-medium text-neutral-700">Lokasi Penyimpanan *</label>
                            <input type="text" name="storage_location" id="storage_location" value="{{ old('storage_location') }}" class="flux-input" placeholder="Nomor rak/brankas/gudang..." required />
                        </div>
                    </div>
                </div>

                <hr class="border-neutral-200" />

                <div class="flex items-center justify-end gap-3">
                    <flux:button as="a" :href="route('collaterals.index')" wire:navigate variant="ghost">
                        Batal
                    </flux:button>
                    <flux:button type="submit" variant="primary" icon="check">
                        Simpan & Terima Jaminan
                    </flux:button>
                </div>
            </form>
        </flux:card>
    </div>
</x-layouts::app>

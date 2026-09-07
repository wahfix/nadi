<x-layouts::app :title="__('Buat Verifikasi Identitas')">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('verifications.index')" wire:navigate>Verifikasi Identitas</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Buat Verifikasi</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl">Buat Verifikasi Identitas</flux:heading>
            <flux:subheading>Verifikasi identitas fisik pemohon sebelum jaminan diserahkan.</flux:subheading>
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
            <form method="POST" action="{{ route('verifications.store') }}" class="flex flex-col gap-6">
                @csrf

                <div class="flex flex-col gap-1">
                    <flux:heading size="lg">1. Pilih Pinjaman</flux:heading>
                    <select name="loan_id" id="loan_id" class="flux-input mt-2 w-full" required>
                        <option value="">— Pilih Pinjaman —</option>
                        @foreach ($loans as $loan)
                            <option
                                value="{{ $loan->id }}"
                                {{ (old('loan_id', $preselectedLoan?->id) == $loan->id) ? 'selected' : '' }}
                            >
                                {{ $loan->loan_number }} — {{ $loan->customer?->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <hr class="border-neutral-200" />

                <div class="flex flex-col gap-1">
                    <flux:heading size="lg">2. Data Verifikasi</flux:heading>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <label for="verification_method" class="text-sm font-medium text-neutral-700">Metode Verifikasi *</label>
                            <select name="verification_method" id="verification_method" class="flux-input" required>
                                <option value="">— Pilih Metode —</option>
                                @foreach ($methodOptions as $value => $label)
                                    <option value="{{ $value }}" {{ old('verification_method') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label for="result" class="text-sm font-medium text-neutral-700">Hasil Verifikasi *</label>
                            <select name="result" id="result" class="flux-input" required>
                                <option value="">— Pilih Hasil —</option>
                                @foreach ($resultOptions as $value => $label)
                                    <option value="{{ $value }}" {{ old('result') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label for="verified_name" class="text-sm font-medium text-neutral-700">Nama Pemohon (sesuai identitas) *</label>
                            <input type="text" name="verified_name" id="verified_name" value="{{ old('verified_name') }}" class="flux-input" required />
                        </div>

                        <div class="flex flex-col gap-1">
                            <label for="verified_id_number" class="text-sm font-medium text-neutral-700">Nomor Identitas Pemohon *</label>
                            <input type="text" name="verified_id_number" id="verified_id_number" value="{{ old('verified_id_number') }}" class="flux-input" required />
                        </div>

                        <div class="flex flex-col gap-1 md:col-span-2">
                            <label for="notes" class="text-sm font-medium text-neutral-700">Catatan</label>
                            <textarea name="notes" id="notes" rows="3" class="flux-input" placeholder="Catatan tambahan mengenai proses verifikasi...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <hr class="border-neutral-200" />

                <div class="flex items-center justify-end gap-3">
                    <flux:button as="a" :href="route('verifications.index')" wire:navigate variant="ghost">Batal</flux:button>
                    <flux:button type="submit" variant="primary" icon="check">Simpan Verifikasi</flux:button>
                </div>
            </form>
        </flux:card>
    </div>
</x-layouts::app>

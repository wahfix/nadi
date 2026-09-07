<x-layouts::app :title="'Verifikasi #'.$verification->id">
    @php
        $resultColors = [
            'VERIFIED' => 'green',
            'FAILED' => 'red',
            'REQUIRES_REVIEW' => 'amber',
        ];
    @endphp

    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('verifications.index')" wire:navigate>Verifikasi Identitas</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Verifikasi #{{ $verification->id }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        <x-flash-message type="success" :message="session('success')" />

        <flux:card>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-3">
                        <flux:heading size="xl">Verifikasi Identitas #{{ $verification->id }}</flux:heading>
                        <flux:badge :color="$resultColors[$verification->result] ?? 'neutral'">
                            {{ $resultLabels[$verification->result] ?? $verification->result }}
                        </flux:badge>
                    </div>
                    <flux:text class="text-sm text-neutral-500">
                        {{ format_date_indonesian($verification->created_at, true) }}
                    </flux:text>
                </div>

                <flux:button as="a" :href="route('verifications.print', $verification)" icon="printer">
                    Cetak Hasil
                </flux:button>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Detail Verifikasi</flux:heading>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="flex flex-col gap-1">
                    <flux:text variant="small" class="text-neutral-500">Nama Pemohon</flux:text>
                    <flux:text class="font-semibold">{{ $verification->verified_name }}</flux:text>
                </div>
                <div class="flex flex-col gap-1">
                    <flux:text variant="small" class="text-neutral-500">Nomor Identitas</flux:text>
                    <flux:text class="font-mono">{{ $verification->verified_id_number }}</flux:text>
                </div>
                <div class="flex flex-col gap-1">
                    <flux:text variant="small" class="text-neutral-500">Nasabah Terkait</flux:text>
                    <flux:text>
                        <a href="{{ route('customers.show', $verification->customer) }}" wire:navigate class="font-medium hover:underline">
                            {{ $verification->customer?->full_name ?? '-' }}
                        </a>
                    </flux:text>
                </div>
                <div class="flex flex-col gap-1">
                    <flux:text variant="small" class="text-neutral-500">Nomor Pinjaman</flux:text>
                    <flux:text class="font-mono text-sm">{{ $verification->loan?->loan_number ?? '-' }}</flux:text>
                </div>
                <div class="flex flex-col gap-1">
                    <flux:text variant="small" class="text-neutral-500">Metode Verifikasi</flux:text>
                    <flux:text>{{ $methodLabels[$verification->verification_method] ?? $verification->verification_method }}</flux:text>
                </div>
                <div class="flex flex-col gap-1">
                    <flux:text variant="small" class="text-neutral-500">Petugas Verifikator</flux:text>
                    <flux:text>{{ $verification->verifier?->name ?? '-' }}</flux:text>
                </div>
                <div class="flex flex-col gap-1">
                    <flux:text variant="small" class="text-neutral-500">Waktu Verifikasi</flux:text>
                    <flux:text>{{ format_date_indonesian($verification->verification_timestamp, true) }}</flux:text>
                </div>
                @if ($verification->notes)
                    <div class="flex flex-col gap-1 md:col-span-2">
                        <flux:text variant="small" class="text-neutral-500">Catatan</flux:text>
                        <flux:text>{{ $verification->notes }}</flux:text>
                    </div>
                @endif
            </div>
        </flux:card>
    </div>
</x-layouts::app>

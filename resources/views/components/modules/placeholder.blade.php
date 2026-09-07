@props([
    'title' => '',
    'description' => '',
    'icon' => 'clipboard-document-list',
    'phase' => null,
])

<x-layouts::app :title="$title">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:heading size="xl">{{ $title }}</flux:heading>
            <flux:subheading>{{ $description }}</flux:subheading>
        </div>

        <div class="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-neutral-300 bg-white p-10 text-center dark:border-neutral-700 dark:bg-zinc-900">
            <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
                <flux:icon :name="$icon" variant="outline" class="size-10 text-neutral-400 dark:text-neutral-500" />
            </div>

            <flux:heading size="lg" class="mt-6">Modul Menunggu Implementasi</flux:heading>

            <flux:text class="mt-2 max-w-md">
                Modul <strong>{{ $title }}</strong> akan diaktifkan pada
                <strong>Fase {{ $phase }}</strong> dari 9 fase pembangunan NADI.
                Halaman ini merupakan pengaman navigasi agar struktur aplikasi tetap koheren sejak fase fondasi.
            </flux:text>

            <flux:button as="a" :href="route('dashboard')" variant="ghost" wire:navigate class="mt-8">
                Kembali ke Dasbor
            </flux:button>
        </div>
    </div>
</x-layouts::app>
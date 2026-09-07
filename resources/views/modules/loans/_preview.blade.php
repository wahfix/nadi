<flux:card>
    <flux:heading size="lg">Pratinjau Kalkulasi</flux:heading>
    <flux:text class="text-sm">Nilai diperbarui otomatis ketika input berubah.</flux:text>

    <div class="mt-4" x-show="error" x-cloak>
        <div class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-950/40 dark:text-red-300" x-text="error"></div>
    </div>

    <div class="mt-4 flex flex-col gap-3" x-show="!loading && preview" x-cloak>
        <div class="flex items-center justify-between border-b border-neutral-100 pb-2 dark:border-neutral-700">
            <span class="text-sm text-neutral-500">Pokok Pinjaman</span>
            <span class="text-sm font-semibold" x-text="previewPrincipal"></span>
        </div>
        <div class="flex items-center justify-between border-b border-neutral-100 pb-2 dark:border-neutral-700">
            <span class="text-sm text-neutral-500">Total Bunga</span>
            <span class="text-sm font-semibold" x-text="previewTotalInterest"></span>
        </div>
        <div class="flex items-center justify-between border-b border-neutral-100 pb-2 dark:border-neutral-700">
            <span class="text-sm text-neutral-500">Total Kewajiban</span>
            <span class="text-sm font-semibold" x-text="previewTotalPayable"></span>
        </div>
        <div class="flex items-center justify-between border-b border-neutral-100 pb-2 dark:border-neutral-700">
            <span class="text-sm text-neutral-500">Angsuran / Periode</span>
            <span class="text-sm font-semibold text-green-700 dark:text-green-400" x-text="previewInstallment"></span>
        </div>
        <div class="flex items-center justify-between border-b border-neutral-100 pb-2 dark:border-neutral-700">
            <span class="text-sm text-neutral-500">Jatuh Tempo Pertama</span>
            <span class="text-sm" x-text="previewFirstDue"></span>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-sm text-neutral-500">Jatuh Tempo Akhir</span>
            <span class="text-sm" x-text="previewMaturity"></span>
        </div>
    </div>

    <div class="mt-4 flex flex-col items-center justify-center gap-2 p-6 text-center" x-show="!loading && !preview" x-cloak>
        <flux:icon name="calculator" variant="outline" class="size-8 text-neutral-400" />
        <flux:text class="text-sm">Lengkapi pokok, bunga, tenor, dan tanggal jatuh tempo untuk melihat pratinjau.</flux:text>
    </div>

    <div class="mt-4 flex items-center justify-center gap-2 text-sm text-neutral-500" x-show="loading" x-cloak>
        <flux:icon name="arrow-path" variant="outline" class="size-4 animate-spin" />
        Menghitung...
    </div>
</flux:card>
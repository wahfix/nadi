@props([
    'title' => 'Laporan',
    'subtitle' => '',
    'reportName' => '',
])

<x-layouts::app :title="__($title)">
    <style>
        @media print {
            body {
                background: white !important;
            }

            flux-sidebar,
            flux-header,
            .no-print {
                display: none !important;
            }

            flux-main {
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
            }

            .pagination-links {
                display: none !important;
            }
        }

        .print-only {
            display: none;
        }

        @media print {
            .print-only {
                display: block;
            }
        }
    </style>

    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex flex-col gap-1.5">
                <flux:heading size="xl">{{ $title }}</flux:heading>
                @if ($subtitle)
                    <flux:subheading>{{ $subtitle }}</flux:subheading>
                @endif
            </div>

            <button
                onclick="window.print()"
                class="no-print inline-flex items-center gap-2 rounded-lg bg-neutral-900 px-4 py-2 text-sm font-semibold text-white hover:bg-neutral-700"
            >
                Cetak Laporan
            </button>
        </div>

        {{ $filters }}

        <flux:card>
            {{ $slot }}
        </flux:card>

        <div class="print-only rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3 text-xs text-neutral-600 dark:border-neutral-700 dark:bg-neutral-800">
            <p class="font-semibold">NADI — Loan Management System</p>
            <p>Laporan: {{ $reportName }} • Dicetak pada {{ format_date_indonesian(now(), true) }}</p>
        </div>
    </div>
</x-layouts::app>
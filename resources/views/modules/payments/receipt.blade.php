<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kuitansi {{ $payment->payment_number }} — NADI</title>
    @fonts
    @vite(['resources/css/app.css'])
    @fluxAppearance
    <style>
        @page {
            size: A5 landscape;
            margin: 12mm;
        }

        @media print {
            body {
                background: white !important;
            }

            .no-print {
                display: none !important;
            }

            .print-block {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="bg-neutral-200 p-6 text-neutral-900">
    <div class="mx-auto max-w-3xl">
        <div class="mb-4 flex justify-end no-print">
            <button onclick="window.print()"
                    class="inline-flex items-center gap-2 rounded-lg bg-neutral-900 px-4 py-2 text-sm font-semibold text-white hover:bg-neutral-700">
                Cetak Kuitansi
            </button>
        </div>

        <div class="print-block rounded-2xl border border-neutral-300 bg-white p-8 shadow-sm">
            {{-- Kepala Kuitansi --}}
            <div class="flex items-start justify-between border-b border-neutral-300 pb-5">
                <div class="flex items-center gap-3">
                    <div class="flex aspect-square size-12 items-center justify-center rounded-lg bg-emerald-600 text-xl font-bold text-white">
                        N
                    </div>
                    <div>
                        <div class="text-xl font-bold tracking-tight">NADI</div>
                        <div class="text-xs text-neutral-500">Loan Management System</div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-lg font-bold uppercase tracking-wide">Kuitansi Pembayaran</div>
                    <div class="font-mono text-sm text-neutral-600">No: {{ $payment->payment_number }}</div>
                </div>
            </div>

            {{-- Identitas Nasabah --}}
            <div class="mt-5 grid grid-cols-2 gap-x-8 gap-y-3 text-sm">
                <div class="flex flex-col gap-0.5">
                    <span class="text-xs text-neutral-500">Diterima Dari</span>
                    <span class="font-semibold">{{ $payment->customer?->full_name ?? '-' }}</span>
                    <span class="font-mono text-xs text-neutral-500">{{ $payment->customer?->customer_code ?? '-' }}</span>
                </div>
                <div class="flex flex-col gap-0.5">
                    <span class="text-xs text-neutral-500">Tanggal Pembayaran</span>
                    <span class="font-semibold">{{ format_date_indonesian($payment->payment_date, true) }}</span>
                    <span class="font-mono text-xs text-neutral-500">Pinjaman: {{ $payment->loan?->loan_number ?? '-' }}</span>
                </div>
            </div>

            {{-- Nominal --}}
            <div class="mt-6 rounded-xl border border-neutral-300 bg-neutral-50 p-5 text-center">
                <div class="text-xs uppercase tracking-wide text-neutral-500">Sudah Diterima Sejumlah</div>
                <div class="mt-1 text-2xl font-extrabold tracking-tight">{{ format_rupiah($payment->amount) }}</div>
                <div class="mt-1 text-sm text-neutral-600">Dengan alokasi pokok {{ format_rupiah($payment->principal_component) }}, bunga {{ format_rupiah($payment->interest_component) }}, denda {{ format_rupiah($payment->penalty_component) }}</div>
            </div>

            {{-- Rincian Metode & Alokasi --}}
            <div class="mt-6 grid grid-cols-2 gap-x-8 gap-y-4 text-sm">
                <div class="flex flex-col gap-0.5">
                    <span class="text-xs text-neutral-500">Metode Pembayaran</span>
                    <span class="font-semibold">
                        {{ ['CASH' => 'Tunai', 'BANK_TRANSFER' => 'Transfer Bank', 'QRIS' => 'QRIS', 'OTHER' => 'Lainnya'][$payment->payment_method] ?? $payment->payment_method }}
                    </span>
                    @if ($payment->reference_number)
                        <span class="font-mono text-xs text-neutral-500">Ref: {{ $payment->reference_number }}</span>
                    @endif
                </div>
                <div class="flex flex-col gap-0.5">
                    <span class="text-xs text-neutral-500">Diterima Oleh</span>
                    <span class="font-semibold">{{ $payment->receivedBy?->name ?? '-' }}</span>
                    @if ($payment->notes)
                        <span class="text-xs text-neutral-500">Catatan: {{ $payment->notes }}</span>
                    @endif
                </div>
            </div>

            {{-- Tanda Tangan --}}
            <div class="mt-10 grid grid-cols-2 gap-12 text-center text-sm">
                <div class="flex flex-col gap-1">
                    <span class="text-neutral-500">Petugas / Penerima</span>
                    <span class="mt-12 border-t border-neutral-400 pt-1 font-semibold">{{ $payment->receivedBy?->name ?? '-' }}</span>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-neutral-500">Nasabah / Pembayar</span>
                    <span class="mt-12 border-t border-neutral-400 pt-1 font-semibold">{{ $payment->customer?->full_name ?? '-' }}</span>
                </div>
            </div>

            @if ($payment->isReversed())
                <div class="mt-6 rounded-lg bg-red-50 p-3 text-center text-sm font-semibold text-red-700">
                    Pembayaran ini telah dibalikkan dan tidak lagi sah sebagai bukti setoran sah.
                </div>
            @endif
        </div>

        <div class="mt-3 text-center text-xs text-neutral-500 no-print">
            Dokumen ini dicetak dari sistem NADI dan berlaku sebagai tanda terima setoran resmi.
        </div>
    </div>

    @fluxScripts
    <script>window.addEventListener('load', function () { window.print(); });</script>
</body>
</html>
<x-layouts::app :title="$loan->loan_number">
    @php
        $statusLabels = [
            'DRAFT' => 'Draft',
            'SUBMITTED' => 'Disubmit',
            'UNDER_REVIEW' => 'Dalam Review',
            'APPROVED' => 'Disetujui',
            'REJECTED' => 'Ditolak',
            'READY_FOR_DISBURSEMENT' => 'Siap Dicairkan',
            'ACTIVE' => 'Aktif',
            'OVERDUE' => 'Menunggak',
            'COMPLETED' => 'Lunas',
            'DEFAULTED' => 'Macet',
            'CANCELLED' => 'Dibatalkan',
        ];
        $statusColors = [
            'DRAFT' => 'neutral',
            'SUBMITTED' => 'blue',
            'UNDER_REVIEW' => 'amber',
            'APPROVED' => 'blue',
            'REJECTED' => 'red',
            'READY_FOR_DISBURSEMENT' => 'blue',
            'ACTIVE' => 'green',
            'OVERDUE' => 'amber',
            'COMPLETED' => 'green',
            'DEFAULTED' => 'red',
            'CANCELLED' => 'neutral',
        ];
        $installmentLabels = [
            'PENDING' => 'Belum Bayar',
            'PARTIALLY_PAID' => 'Dibayar Sebagian',
            'PAID' => 'Lunas',
            'OVERDUE' => 'Menunggak',
            'WAIVED' => 'Dihapuskan',
        ];
        $installmentColors = [
            'PENDING' => 'blue',
            'PARTIALLY_PAID' => 'amber',
            'PAID' => 'green',
            'OVERDUE' => 'red',
            'WAIVED' => 'neutral',
        ];
    @endphp

    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('loans.index')" wire:navigate>Pinjaman</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $loan->loan_number }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        <x-flash-message type="success" :message="session('success')" />
        <x-flash-message type="error" :message="session('error')" />

        <flux:card>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-2">
                        <flux:heading size="xl" class="font-mono">{{ $loan->loan_number }}</flux:heading>
                        <flux:badge :color="$statusColors[$loan->status] ?? 'neutral'">
                            {{ $statusLabels[$loan->status] ?? $loan->status }}
                        </flux:badge>
                    </div>
                    <flux:text class="text-neutral-500">
                        Nasabah:
                        <a href="{{ route('customers.show', $loan->customer) }}" wire:navigate class="font-medium text-neutral-800 hover:underline">
                            {{ $loan->customer?->full_name }}
                        </a>
                        ({{ $loan->customer?->customer_code }})
                    </flux:text>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @can('update', $loan)
                        @if ($loan->status === 'DRAFT')
                            <flux:button as="a" :href="route('loans.edit', $loan)" wire:navigate variant="primary" icon="pencil-square">
                                Ubah
                            </flux:button>
                        @endif
                    @endcan

                    @can('update', $loan)
                        @if ($loan->status === 'DRAFT')
                            <form method="POST" action="{{ route('loans.submit', $loan) }}">
                                @csrf
                                <flux:button variant="primary" type="submit">
                                    Submit Pengajuan
                                </flux:button>
                            </form>
                        @endif
                    @endcan

                    @can('review', $loan)
                        @if ($loan->status === 'SUBMITTED')
                            <form method="POST" action="{{ route('loans.review', $loan) }}">
                                @csrf
                                <flux:button variant="primary" type="submit">
                                    Mulai Review
                                </flux:button>
                            </form>
                        @endif
                    @endcan

                    @can('approve', $loan)
                        @if ($loan->status === 'UNDER_REVIEW')
                            <flux:button variant="primary" type="button" onclick="document.getElementById('approve-loan-modal').showModal()">
                                Setujui
                            </flux:button>
                            <flux:button variant="danger" type="button" onclick="document.getElementById('reject-loan-modal').showModal()">
                                Tolak
                            </flux:button>
                        @endif
                    @endcan

                    @can('approve', $loan)
                        @if ($loan->status === 'APPROVED')
                            <form method="POST" action="{{ route('loans.ready', $loan) }}">
                                @csrf
                                <flux:button variant="primary" type="submit">
                                    Siapkan Pencairan
                                </flux:button>
                            </form>
                        @endif
                    @endcan

                    @can('disburse', $loan)
                        @if ($loan->status === 'READY_FOR_DISBURSEMENT')
                            <flux:button variant="primary" type="button" onclick="document.getElementById('disburse-loan-modal').showModal()">
                                Cairkan Pinjaman
                            </flux:button>
                        @endif
                    @endcan

                    @can('update', $loan)
                        @if (in_array($loan->status, ['DRAFT', 'SUBMITTED', 'READY_FOR_DISBURSEMENT'], true))
                            <form method="POST" action="{{ route('loans.cancel', $loan) }}">
                                @csrf
                                <flux:button variant="ghost" type="submit" onclick="return confirm('Batalkan pengajuan pinjaman ini?')">
                                    Batalkan
                                </flux:button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>
        </flux:card>

        {{-- Modal: Setujui --}}
        <dialog id="approve-loan-modal" class="m-auto rounded-2xl bg-white p-0 shadow-2xl dark:bg-zinc-800">
            <div class="flex min-w-96 max-w-lg flex-col gap-4 p-6">
                <flux:heading size="lg">Konfirmasi Persetujuan Pinjaman</flux:heading>
                <p class="text-sm text-neutral-600 dark:text-neutral-300">
                    Menyetujui pengajuan <strong class="font-mono">{{ $loan->loan_number }}</strong> sebesar
                    {{ format_rupiah($loan->principal_amount) }} atas nama {{ $loan->customer?->full_name }}?
                </p>
                <div class="flex items-center justify-end gap-3">
                    <flux:button variant="ghost" type="button" onclick="document.getElementById('approve-loan-modal').close()">
                        Batal
                    </flux:button>
                    <form method="POST" action="{{ route('loans.approve', $loan) }}">
                        @csrf
                        <flux:button variant="primary" type="submit">
                            Ya, Setujui
                        </flux:button>
                    </form>
                </div>
            </div>
        </dialog>

        {{-- Modal: Tolak --}}
        <dialog id="reject-loan-modal" class="m-auto rounded-2xl bg-white p-0 shadow-2xl dark:bg-zinc-800">
            <div class="flex min-w-96 max-w-lg flex-col gap-4 p-6">
                <flux:heading size="lg">Konfirmasi Penolakan Pinjaman</flux:heading>
                <p class="text-sm text-neutral-600 dark:text-neutral-300">
                    Alasan penolakan untuk pengajuan <strong class="font-mono">{{ $loan->loan_number }}</strong> wajib diisi.
                </p>
                <form method="POST" action="{{ route('loans.reject', $loan) }}" class="flex flex-col gap-4">
                    @csrf
                    <flux:textarea name="reason" label="Alasan Penolakan" rows="3" required
                                   placeholder="Contoh: penghasilan tidak mencukupi rasio angsuran"></flux:textarea>
                    @error('reason')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <div class="flex items-center justify-end gap-3">
                        <flux:button variant="ghost" type="button" onclick="document.getElementById('reject-loan-modal').close()">
                            Batal
                        </flux:button>
                        <flux:button variant="danger" type="submit">
                            Ya, Tolak
                        </flux:button>
                    </div>
                </form>
            </div>
        </dialog>

        {{-- Modal: Cairkan --}}
        <dialog id="disburse-loan-modal" class="m-auto rounded-2xl bg-white p-0 shadow-2xl dark:bg-zinc-800">
            <div class="flex min-w-96 max-w-lg flex-col gap-4 p-6">
                <flux:heading size="lg">Konfirmasi Pencairan Pinjaman</flux:heading>
                <p class="text-sm text-neutral-600 dark:text-neutral-300">
                    Mencairkan pinjaman <strong class="font-mono">{{ $loan->loan_number }}</strong> sebesar
                    {{ format_rupiah($loan->principal_amount) }}? Jadwal angsuran akan dibuat otomatis.
                </p>
                <div class="flex items-center justify-end gap-3">
                    <flux:button variant="ghost" type="button" onclick="document.getElementById('disburse-loan-modal').close()">
                        Batal
                    </flux:button>
                    <form method="POST" action="{{ route('loans.disburse', $loan) }}">
                        @csrf
                        <flux:button variant="primary" type="submit">
                            Ya, Cairkan
                        </flux:button>
                    </form>
                </div>
            </div>
        </dialog>

        <div x-data="{ tab: 'ringkasan' }" class="flex flex-col gap-4">
            <div class="flex flex-wrap gap-1.5">
                @php
                    $tabs = [
                        'ringkasan' => 'Ringkasan',
                        'angsuran' => 'Angsuran',
                        'pembayaran' => 'Pembayaran',
                        'jaminan' => 'Jaminan',
                        'penagihan' => 'Penagihan',
                        'riwayat' => 'Riwayat',
                    ];
                @endphp

                @foreach ($tabs as $key => $label)
                    <button
                        type="button"
                        @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-700 dark:text-neutral-200'"
                        class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Ringkasan --}}
            <div x-show="tab === 'ringkasan'" x-cloak>
                <flux:card>
                    <flux:heading size="lg">Rincian Kontrak Pinjaman</flux:heading>

                    <div class="mt-5 grid gap-x-8 gap-y-4 md:grid-cols-2 lg:grid-cols-3">
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Pokok Pinjaman</flux:text>
                            <flux:text class="font-medium">{{ format_rupiah($loan->principal_amount) }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Suku Bunga</flux:text>
                            <flux:text class="font-medium">{{ format_interest_rate($loan->interest_rate, $loan->installment_frequency === 'WEEKLY' ? 'minggu' : 'bulan') }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Metode Bunga</flux:text>
                            <flux:text class="font-medium">{{ $loan->interest_method === 'FLAT' ? 'Flat (Tetap)' : 'Efektif Menurun (Anuitas)' }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Tenor</flux:text>
                            <flux:text class="font-medium">{{ $loan->tenor }} periode</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Frekuensi Angsuran</flux:text>
                            <flux:text class="font-medium">{{ $loan->installment_frequency === 'WEEKLY' ? 'Mingguan' : 'Bulanan' }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Angsuran per Periode</flux:text>
                            <flux:text class="font-medium">{{ format_rupiah($loan->installment_amount) }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Total Bunga</flux:text>
                            <flux:text class="font-medium">{{ format_rupiah($loan->total_interest) }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Total Kewajiban</flux:text>
                            <flux:text class="font-medium">{{ format_rupiah($loan->total_payable) }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Tanggal Pencairan</flux:text>
                            <flux:text class="font-medium">{{ format_date($loan->disbursement_date) }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Jatuh Tempo Pertama</flux:text>
                            <flux:text class="font-medium">{{ format_date($loan->first_due_date) }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Jatuh Tempo Akhir</flux:text>
                            <flux:text class="font-medium">{{ format_date($loan->maturity_date) }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Status Pinjaman</flux:text>
                            <flux:text class="font-medium">{{ $statusLabels[$loan->status] ?? $loan->status }}</flux:text>
                        </div>
                    </div>
                </flux:card>

                <flux:card class="mt-4">
                    <flux:heading size="lg">Saldo Kewajiban Berjalan</flux:heading>
                    <div class="mt-5 grid gap-x-8 gap-y-4 md:grid-cols-2 lg:grid-cols-4">
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Sisa Pokok</flux:text>
                            <flux:text class="font-medium">{{ format_rupiah($loan->outstanding_principal) }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Sisa Bunga</flux:text>
                            <flux:text class="font-medium">{{ format_rupiah($loan->outstanding_interest) }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Sisa Denda</flux:text>
                            <flux:text class="font-medium">{{ format_rupiah($loan->outstanding_penalty) }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Sisa Total</flux:text>
                            <flux:text class="font-semibold text-neutral-900 dark:text-white">{{ format_rupiah($loan->outstanding_total) }}</flux:text>
                        </div>
                    </div>
                </flux:card>

                <flux:card class="mt-4">
                    <flux:heading size="lg">Proses Persetujuan & Pencairan</flux:heading>
                    <div class="mt-5 grid gap-x-8 gap-y-4 md:grid-cols-2 lg:grid-cols-3">
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Petugas Pembuat</flux:text>
                            <flux:text class="font-medium">{{ $loan->creator?->name ?? '-' }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Disetujui Oleh</flux:text>
                            <flux:text class="font-medium">{{ $loan->approver?->name ?? '-' }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Tanggal Persetujuan</flux:text>
                            <flux:text class="font-medium">{{ $loan->approved_at ? format_date_indonesian($loan->approved_at, true) : '-' }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Tanggal Pencairan Efektif</flux:text>
                            <flux:text class="font-medium">{{ $loan->disbursed_at ? format_date_indonesian($loan->disbursed_at, true) : '-' }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Tanggal Lunas</flux:text>
                            <flux:text class="font-medium">{{ $loan->completed_at ? format_date_indonesian($loan->completed_at, true) : '-' }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Dibuat Pada</flux:text>
                            <flux:text class="font-medium">{{ format_date_indonesian($loan->created_at, true) }}</flux:text>
                        </div>
                    </div>
                </flux:card>
            </div>

            {{-- Angsuran --}}
            <div x-show="tab === 'angsuran'" x-cloak>
                <flux:card>
                    <flux:heading size="lg">Jadwal Angsuran</flux:heading>
                    <div class="mt-4">
                        @if ($loan->installments->isEmpty())
                            <div class="flex flex-col items-center justify-center gap-2 p-8 text-center">
                                <flux:icon name="calendar-days" variant="outline" class="size-8 text-neutral-400" />
                                <flux:text>
                                    Belum ada jadwal angsuran pada pinjaman ini.
                                    Jadwal dibuat otomatis saat pinjaman dicairkan.
                                </flux:text>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <flux:table>
                                    <flux:table.columns>
                                        <flux:table.column>No</flux:table.column>
                                        <flux:table.column>Jatuh Tempo</flux:table.column>
                                        <flux:table.column>Pokok</flux:table.column>
                                        <flux:table.column>Bunga</flux:table.column>
                                        <flux:table.column>Total Tagihan</flux:table.column>
                                        <flux:table.column>Terbayar</flux:table.column>
                                        <flux:table.column>Sisa</flux:table.column>
                                        <flux:table.column>Status</flux:table.column>
                                    </flux:table.columns>
                                    <flux:table.rows>
                                        @foreach ($loan->installments as $installment)
                                            <flux:table.row>
                                                <flux:table.cell class="font-mono text-sm">{{ $installment->installment_number }}</flux:table.cell>
                                                <flux:table.cell>{{ format_date($installment->due_date) }}</flux:table.cell>
                                                <flux:table.cell>{{ format_rupiah($installment->principal_due) }}</flux:table.cell>
                                                <flux:table.cell>{{ format_rupiah($installment->interest_due) }}</flux:table.cell>
                                                <flux:table.cell>{{ format_rupiah($installment->total_due) }}</flux:table.cell>
                                                <flux:table.cell>{{ format_rupiah($installment->total_paid) }}</flux:table.cell>
                                                <flux:table.cell>{{ format_rupiah($installment->remaining_amount) }}</flux:table.cell>
                                                <flux:table.cell>
                                                    <flux:badge :color="$installmentColors[$installment->status] ?? 'neutral'">
                                                        {{ $installmentLabels[$installment->status] ?? $installment->status }}
                                                    </flux:badge>
                                                </flux:table.cell>
                                            </flux:table.row>
                                        @endforeach
                                    </flux:table.rows>
                                </flux:table>
                            </div>
                        @endif
                    </div>
                </flux:card>
            </div>

            {{-- Pembayaran (Fase 4) --}}
            <div x-show="tab === 'pembayaran'" x-cloak>
                <flux:card>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <flux:heading size="lg">Riwayat Pembayaran</flux:heading>
                        @can('create', \App\Models\Payment::class)
                            @if (in_array($loan->status, ['ACTIVE', 'OVERDUE'], true) && $loan->outstanding_total > 0)
                                <flux:button as="a" :href="route('payments.create', ['loan' => $loan->id])" wire:navigate variant="primary" icon="plus">
                                    Catat Pembayaran
                                </flux:button>
                            @endif
                        @endcan
                    </div>
                    <div class="mt-4">
                        @if ($loan->payments->isEmpty())
                            <div class="flex flex-col items-center justify-center gap-2 p-8 text-center">
                                <flux:icon name="banknotes" variant="outline" class="size-8 text-neutral-400" />
                                <flux:text>Belum ada pembayaran untuk pinjaman ini.</flux:text>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <flux:table>
                                    <flux:table.columns>
                                        <flux:table.column>No. Pembayaran</flux:table.column>
                                        <flux:table.column>Tanggal</flux:table.column>
                                        <flux:table.column>Nominal</flux:table.column>
                                        <flux:table.column>Alokasi (Pokok / Bunga / Denda)</flux:table.column>
                                        <flux:table.column>Metode</flux:table.column>
                                        <flux:table.column>Status</flux:table.column>
                                        <flux:table.column>Aksi</flux:table.column>
                                    </flux:table.columns>
                                    <flux:table.rows>
                                        @foreach ($loan->payments as $payment)
                                            <flux:table.row>
                                                <flux:table.cell>
                                                    <a href="{{ route('payments.show', $payment) }}" wire:navigate class="font-mono text-sm font-medium text-neutral-900 hover:underline">
                                                        {{ $payment->payment_number }}
                                                    </a>
                                                </flux:table.cell>
                                                <flux:table.cell>{{ format_date($payment->payment_date) }}</flux:table.cell>
                                                <flux:table.cell>{{ format_rupiah($payment->amount) }}</flux:table.cell>
                                                <flux:table.cell class="text-xs text-neutral-600">
                                                    {{ format_rupiah($payment->principal_component) }} /
                                                    {{ format_rupiah($payment->interest_component) }} /
                                                    {{ format_rupiah($payment->penalty_component) }}
                                                </flux:table.cell>
                                                <flux:table.cell>{{ $payment->payment_method }}</flux:table.cell>
                                                <flux:table.cell>
                                                    @if ($payment->isReversed())
                                                        <flux:badge color="red">Dibatalkan</flux:badge>
                                                    @else
                                                        <flux:badge color="green">Sah</flux:badge>
                                                    @endif
                                                </flux:table.cell>
                                                <flux:table.cell>
                                                    <flux:button as="a" :href="route('payments.show', $payment)" wire:navigate size="sm" variant="ghost">
                                                        Lihat
                                                    </flux:button>
                                                </flux:table.cell>
                                            </flux:table.row>
                                        @endforeach
                                    </flux:table.rows>
                                </flux:table>
                            </div>
                        @endif
                    </div>
                </flux:card>
            </div>

            {{-- Jaminan (Fase 6) --}}
            <div x-show="tab === 'jaminan'" x-cloak>
                <flux:card>
                    <flux:heading size="lg">Agunan / Jaminan</flux:heading>
                    <div class="mt-4">
                        @if ($loan->collaterals->isEmpty())
                            <div class="flex flex-col items-center justify-center gap-2 p-8 text-center">
                                <flux:icon name="wallet" variant="outline" class="size-8 text-neutral-400" />
                                <flux:text>Belum ada jaminan untuk pinjaman ini.</flux:text>
                            </div>
                        @endif
                    </div>
                </flux:card>
            </div>

            {{-- Penagihan (Fase 5) --}}
            <div x-show="tab === 'penagihan'" x-cloak>
                <flux:card>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <flux:heading size="lg">Aktivitas Penagihan</flux:heading>
                        @can('create', \App\Models\CollectionActivity::class)
                            @if (in_array($loan->status, ['ACTIVE', 'OVERDUE'], true) && $loan->outstanding_total > 0)
                                <flux:button as="a" :href="route('collections.create', ['loan' => $loan->id])" wire:navigate variant="primary" icon="plus">
                                    Catat Aktivitas
                                </flux:button>
                            @endif
                        @endcan
                    </div>
                    <div class="mt-4">
                        @if ($loan->collectionActivities->isEmpty())
                            <div class="flex flex-col items-center justify-center gap-2 p-8 text-center">
                                <flux:icon name="hand-raised" variant="outline" class="size-8 text-neutral-400" />
                                <flux:text>Belum ada aktivitas penagihan untuk pinjaman ini.</flux:text>
                            </div>
                        @else
                            @php
                                $activities = $loan->collectionActivities->sortByDesc('contact_date');
                                $methodLabels = ['PHONE' => 'Telepon', 'WHATSAPP' => 'WhatsApp', 'IN_PERSON' => 'Kunjungan', 'OTHER' => 'Lainnya'];
                                $resultLabels = ['PAID' => 'Sudah Membayar', 'PROMISE_TO_PAY' => 'Janji Bayar', 'NO_RESPONSE' => 'Tidak Ada Respons', 'CONTACT_FAILED' => 'Kontak Gagal', 'DISPUTED' => 'Sanggahan', 'OTHER' => 'Lainnya'];
                                $resultColors = ['PAID' => 'green', 'PROMISE_TO_PAY' => 'emerald', 'NO_RESPONSE' => 'neutral', 'CONTACT_FAILED' => 'red', 'DISPUTED' => 'amber', 'OTHER' => 'neutral'];
                            @endphp
                            <div class="overflow-x-auto">
                                <flux:table>
                                    <flux:table.columns>
                                        <flux:table.column>Waktu Kontak</flux:table.column>
                                        <flux:table.column>Metode</flux:table.column>
                                        <flux:table.column>Hasil</flux:table.column>
                                        <flux:table.column>Janji Bayar</flux:table.column>
                                        <flux:table.column>Petugas</flux:table.column>
                                        <flux:table.column>Catatan</flux:table.column>
                                    </flux:table.columns>
                                    <flux:table.rows>
                                        @foreach ($activities as $activity)
                                            <flux:table.row>
                                                <flux:table.cell>{{ format_date_indonesian($activity->contact_date, true) }}</flux:table.cell>
                                                <flux:table.cell>{{ $methodLabels[$activity->contact_method] ?? $activity->contact_method }}</flux:table.cell>
                                                <flux:table.cell>
                                                    <flux:badge :color="$resultColors[$activity->result] ?? 'neutral'">
                                                        {{ $resultLabels[$activity->result] ?? $activity->result }}
                                                    </flux:badge>
                                                </flux:table.cell>
                                                <flux:table.cell>
                                                    @if ($activity->result === \App\Models\CollectionActivity::RESULT_PROMISE_TO_PAY)
                                                        {{ format_date($activity->promise_to_pay_date) }} ·
                                                        {{ format_rupiah((int) $activity->promise_to_pay_amount) }}
                                                    @else
                                                        —
                                                    @endif
                                                </flux:table.cell>
                                                <flux:table.cell class="text-sm">{{ $activity->collector?->name ?? '-' }}</flux:table.cell>
                                                <flux:table.cell class="max-w-xs text-sm text-neutral-600 dark:text-neutral-300">{{ $activity->notes }}</flux:table.cell>
                                            </flux:table.row>
                                        @endforeach
                                    </flux:table.rows>
                                </flux:table>
                            </div>
                        @endif
                    </div>
                </flux:card>
            </div>

            {{-- Riwayat --}}
            <div x-show="tab === 'riwayat'" x-cloak>
                <flux:card>
                    <flux:heading size="lg">Riwayat Perubahan Status</flux:heading>
                    <div class="mt-4">
                        @if ($loan->statusHistories->isEmpty())
                            <div class="flex flex-col items-center justify-center gap-2 p-8 text-center">
                                <flux:icon name="clock" variant="outline" class="size-8 text-neutral-400" />
                                <flux:text>Belum ada riwayat perubahan status.</flux:text>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <flux:table>
                                    <flux:table.columns>
                                        <flux:table.column>Waktu</flux:table.column>
                                        <flux:table.column>Dari</flux:table.column>
                                        <flux:table.column>Ke</flux:table.column>
                                        <flux:table.column>Petugas</flux:table.column>
                                        <flux:table.column>Alasan</flux:table.column>
                                    </flux:table.columns>
                                    <flux:table.rows>
                                        @foreach ($loan->statusHistories as $history)
                                            <flux:table.row>
                                                <flux:table.cell>{{ format_date_indonesian($history->created_at, true) }}</flux:table.cell>
                                                <flux:table.cell>
                                                    <flux:badge :color="$statusColors[$history->from_status] ?? 'neutral'">
                                                        {{ $statusLabels[$history->from_status] ?? $history->from_status }}
                                                    </flux:badge>
                                                </flux:table.cell>
                                                <flux:table.cell>
                                                    <flux:badge :color="$statusColors[$history->to_status] ?? 'neutral'">
                                                        {{ $statusLabels[$history->to_status] ?? $history->to_status }}
                                                    </flux:badge>
                                                </flux:table.cell>
                                                <flux:table.cell>{{ $history->user?->name ?? 'Sistem' }}</flux:table.cell>
                                                <flux:table.cell>{{ $history->reason ?? '-' }}</flux:table.cell>
                                            </flux:table.row>
                                        @endforeach
                                    </flux:table.rows>
                                </flux:table>
                            </div>
                        @endif
                    </div>
                </flux:card>
            </div>
        </div>
    </div>
</x-layouts::app>
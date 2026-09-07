<x-layouts::app :title="__('Penagihan')">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:heading size="xl">Dasbor Penagihan</flux:heading>
            <flux:subheading>Pantauan tunggakan, aktivitas kontak, dan janji bayar oleh Loan Collector.</flux:subheading>
        </div>

        <x-flash-message type="success" :message="session('success')" />
        <x-flash-message type="error" :message="session('error')" />

        @if ($canRecord)
            <div class="flex items-center justify-end">
                <flux:button as="a" :href="route('collections.create')" wire:navigate icon="plus">
                    Catat Aktivitas Penagihan
                </flux:button>
            </div>
        @endif

        {{-- Metrik utama --}}
        <div class="grid grid-cols-2 gap-4 xl:grid-cols-5">
            <flux:card>
                <div class="flex items-center gap-3">
                    <flux:icon name="user-group" variant="outline" class="size-6 text-neutral-400" />
                    <div>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">Nasabah Binaan</p>
                        <p class="text-2xl font-semibold">{{ $stats['total_assigned_customers'] }}</p>
                    </div>
                </div>
            </flux:card>
            <flux:card>
                <div class="flex items-center gap-3">
                    <flux:icon name="calendar-days" variant="outline" class="size-6 text-sky-500" />
                    <div>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">Jatuh Tempo Hari Ini</p>
                        <p class="text-2xl font-semibold">{{ $stats['due_today'] }}</p>
                    </div>
                </div>
            </flux:card>
            <flux:card>
                <div class="flex items-center gap-3">
                    <flux:icon name="exclamation-triangle" variant="outline" class="size-6 text-amber-500" />
                    <div>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">Menunggak</p>
                        <p class="text-2xl font-semibold">{{ $stats['overdue'] }}</p>
                    </div>
                </div>
            </flux:card>
            <flux:card>
                <div class="flex items-center gap-3">
                    <flux:icon name="calendar" variant="outline" class="size-6 text-blue-500" />
                    <div>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">Jatuh Tempo 7 Hari</p>
                        <p class="text-2xl font-semibold">{{ $stats['due_this_week'] }}</p>
                    </div>
                </div>
            </flux:card>
            <flux:card>
                <div class="flex items-center gap-3">
                    <flux:icon name="banknotes" variant="outline" class="size-6 text-emerald-500" />
                    <div>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">Total Sisa Tagihan</p>
                        <p class="text-2xl font-semibold">{{ format_rupiah((int) $stats['total_outstanding']) }}</p>
                    </div>
                </div>
            </flux:card>
        </div>

        {{-- Janji bayar aktif --}}
        <flux:card>
            <flux:heading size="lg">Janji Bayar Aktif</flux:heading>
            @php $promises = $stats['active_promises'] ?? collect(); @endphp

            @if ($promises->isEmpty())
                <div class="flex flex-col items-center justify-center gap-2 p-8 text-center">
                    <flux:icon name="hand-raised" variant="outline" class="size-8 text-neutral-400" />
                    <flux:text>Belum ada janji bayar yang aktif.</flux:text>
                </div>
            @else
                <div class="mt-4 overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Nasabah</flux:table.column>
                            <flux:table.column>Pinjaman</flux:table.column>
                            <flux:table.column>Tanggal Janji</flux:table.column>
                            <flux:table.column>Nominal</flux:table.column>
                            <flux:table.column>Penginput</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($promises as $promise)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <a href="{{ route('customers.show', $promise->customer) }}" wire:navigate class="font-medium hover:underline">
                                            {{ $promise->customer?->full_name ?? '-' }}
                                        </a>
                                    </flux:table.cell>
                                    <flux:table.cell class="font-mono text-xs text-neutral-600">{{ $promise->loan?->loan_number }}</flux:table.cell>
                                    <flux:table.cell>{{ format_date($promise->promise_to_pay_date) }}</flux:table.cell>
                                    <flux:table.cell>{{ format_rupiah((int) $promise->promise_to_pay_amount) }}</flux:table.cell>
                                    <flux:table.cell class="text-sm">{{ $promise->collector?->name ?? '-' }}</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            @endif
        </flux:card>

        {{-- Daftar tunggakan --}}
        <flux:card>
            <flux:heading size="lg">Daftar Tunggakan</flux:heading>

            @if ($unpaidInstallments->isEmpty())
                <div class="flex flex-col items-center justify-center gap-2 p-8 text-center">
                    <flux:icon name="check-circle" variant="outline" class="size-8 text-neutral-400" />
                    <flux:text>Tidak ada angsuran yang menunggak atau belum lunas saat ini.</flux:text>
                </div>
            @else
                <div class="mt-4 overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Nasabah</flux:table.column>
                            <flux:table.column>Pinjaman</flux:table.column>
                            <flux:table.column>Angsuran</flux:table.column>
                            <flux:table.column>Jatuh Tempo</flux:table.column>
                            <flux:table.column>Sisa Tagihan</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            @if ($canRecord)
                                <flux:table.column>Aksi</flux:table.column>
                            @endif
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($unpaidInstallments as $installment)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <a href="{{ route('customers.show', $installment->loan?->customer) }}" wire:navigate class="font-medium hover:underline">
                                            {{ $installment->loan?->customer?->full_name ?? '-' }}
                                        </a>
                                    </flux:table.cell>
                                    <flux:table.cell class="font-mono text-xs text-neutral-600">{{ $installment->loan?->loan_number }}</flux:table.cell>
                                    <flux:table.cell>Angsuran ke-{{ $installment->installment_number }}</flux:table.cell>
                                    <flux:table.cell>{{ format_date($installment->due_date) }}</flux:table.cell>
                                    <flux:table.cell>{{ format_rupiah((int) $installment->remaining_amount) }}</flux:table.cell>
                                    <flux:table.cell>
                                        @if ($installment->due_date < today()->toDateString())
                                            <flux:badge color="amber">Terlambat</flux:badge>
                                        @else
                                            <flux:badge color="blue">Belum Jatuh Tempo</flux:badge>
                                        @endif
                                    </flux:table.cell>
                                    @if ($canRecord)
                                        <flux:table.cell>
                                            <flux:button
                                                as="a"
                                                :href="route('collections.create', ['loan' => $installment->loan_id])"
                                                wire:navigate
                                                size="sm"
                                                variant="ghost"
                                                icon="phone"
                                            >
                                                Hubungi
                                            </flux:button>
                                        </flux:table.cell>
                                    @endif
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            @endif
        </flux:card>

        {{-- Aktivitas terakhir --}}
        <flux:card>
            <flux:heading size="lg">Aktivitas Terakhir</flux:heading>

            @if ($activities->isEmpty())
                <div class="flex flex-col items-center justify-center gap-2 p-8 text-center">
                    <flux:icon name="chat-bubble-left-right" variant="outline" class="size-8 text-neutral-400" />
                    <flux:text>Belum ada aktivitas penagihan yang tercatat.</flux:text>
                </div>
            @else
                <div class="mt-4 overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Waktu Kontak</flux:table.column>
                            <flux:table.column>Nasabah</flux:table.column>
                            <flux:table.column>Metode</flux:table.column>
                            <flux:table.column>Hasil</flux:table.column>
                            <flux:table.column>Janji Bayar</flux:table.column>
                            <flux:table.column>Petugas</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($activities as $activity)
                                <flux:table.row>
                                    <flux:table.cell>{{ format_date_indonesian($activity->contact_date, true) }}</flux:table.cell>
                                    <flux:table.cell>
                                        {{ $activity->customer?->full_name ?? '-' }}
                                        <span class="ml-1 font-mono text-xs text-neutral-500">{{ $activity->loan?->loan_number }}</span>
                                    </flux:table.cell>
                                    <flux:table.cell>{{ $methodOptions[$activity->contact_method] ?? $activity->contact_method }}</flux:table.cell>
                                    <flux:table.cell>{{ $resultOptions[$activity->result] ?? $activity->result }}</flux:table.cell>
                                    <flux:table.cell>
                                        @if ($activity->result === \App\Models\CollectionActivity::RESULT_PROMISE_TO_PAY)
                                            {{ format_date($activity->promise_to_pay_date) }} · {{ format_rupiah((int) $activity->promise_to_pay_amount) }}
                                        @else
                                            —
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell class="text-sm">{{ $activity->collector?->name ?? '-' }}</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>

                <div class="mt-4">
                    {{ $activities->links() }}
                </div>
            @endif
        </flux:card>
    </div>
</x-layouts::app>
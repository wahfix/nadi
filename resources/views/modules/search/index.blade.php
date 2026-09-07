<x-layouts::app :title="__('Pencarian Global')">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:heading size="xl">Pencarian Global</flux:heading>
            <flux:subheading>Cari data berdasarkan nomor resmi NADI: CUS, NADI-LOAN, PAY, COL, atau REL — atau kata kunci nasabah.</flux:subheading>
        </div>

        <x-flash-message type="success" :message="session('success')" />
        <x-flash-message type="error" :message="session('error')" />

        <flux:card>
            <form method="GET" action="{{ route('search.results') }}" class="flex flex-wrap items-end gap-3">
                <div class="min-w-64">
                    <flux:select name="type" required>
                        @foreach ($typeOptions as $value => $label)
                            <flux:select.option :value="$value" :selected="($selectedType ?? 'customer') === $value">
                                {{ $label }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="min-w-72 flex-1">
                    <flux:input
                        type="search"
                        name="q"
                        :value="$query ?? ''"
                        placeholder="Contoh: CUS-2026-000001 atau NADI-LOAN-2026-000001"
                        required
                    />
                </div>

                <flux:button variant="primary" type="submit" icon="magnifying-glass">
                    Cari
                </flux:button>

                @if ($results !== null)
                    <flux:button as="a" :href="route('search.index')" wire:navigate variant="ghost">
                        Reset
                    </flux:button>
                @endif
            </form>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ($typeOptions as $value => $label)
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                        <span class="font-mono font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ match ($value) {
                                'customer' => 'CUS-YYYY-XXXXXX',
                                'loan' => 'NADI-LOAN-YYYY-XXXXXX',
                                'payment' => 'PAY-YYYY-XXXXXX',
                                'collateral' => 'COL-YYYY-XXXXXX',
                                'release' => 'REL-YYYY-XXXXXX',
                            } }}
                        </span>
                        <br />
                        {{ $label }}
                    </div>
                @endforeach
            </div>
        </flux:card>

        @if ($results === null)
            <div class="mb-4 flex items-center gap-2 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-medium text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-300">
                <flux:icon name="information-circle" class="size-4 shrink-0" />
                Pilih jenis data pada dropdown, masukkan nomor atau kata kunci, lalu tekan Cari. Hasil hanya menampilkan data yang diizinkan untuk peran Anda.
            </div>
        @elseif ($results->isEmpty())
            <flux:card>
                <div class="flex flex-col items-center justify-center gap-3 p-10 text-center">
                    <flux:icon name="magnifying-glass" variant="outline" class="size-10 text-neutral-400" />
                    <flux:heading size="lg">Tidak ada hasil ditemukan.</flux:heading>
                    <flux:text>Tidak ada data yang cocok untuk kata kunci &quot;{{ $query }}&quot; pada jenis terpilih.</flux:text>
                    <flux:button as="a" :href="route('search.index')" wire:navigate class="mt-4">
                        Cari Ulang
                    </flux:button>
                </div>
            </flux:card>
        @else
            <flux:card>
                <flux:heading size="md" class="mb-4">
                    Hasil Pencarian
                    <flux:badge class="ms-2">{{ $results->total() }} data</flux:badge>
                </flux:heading>

                @if ($selectedType === 'customer')
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Kode</flux:table.column>
                            <flux:table.column>Nama</flux:table.column>
                            <flux:table.column>Telepon</flux:table.column>
                            <flux:table.column>NIK</flux:table.column>
                            <flux:table.column>Kota</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column>Aksi</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($results as $result)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <span class="font-mono text-sm">{{ $result->customer_code }}</span>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <a href="{{ route('customers.show', $result) }}" wire:navigate class="font-medium hover:underline">
                                            {{ $result->full_name }}
                                        </a>
                                    </flux:table.cell>
                                    <flux:table.cell>{{ $result->phone }}</flux:table.cell>
                                    <flux:table.cell>{{ $result->nik_masked }}</flux:table.cell>
                                    <flux:table.cell>{{ $result->city }}</flux:table.cell>
                                    <flux:table.cell>
                                        @if ($result->status === 'ACTIVE')
                                            <flux:badge color="green">Aktif</flux:badge>
                                        @else
                                            <flux:badge color="neutral">Nonaktif</flux:badge>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:button as="a" :href="route('customers.show', $result)" wire:navigate size="sm" variant="ghost">
                                            Lihat
                                        </flux:button>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @elseif ($selectedType === 'loan')
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Nomor Pinjaman</flux:table.column>
                            <flux:table.column>Nasabah</flux:table.column>
                            <flux:table.column>Pokok</flux:table.column>
                            <flux:table.column>Sisa Tagihan</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column>Aksi</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($results as $result)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <span class="font-mono text-sm">{{ $result->loan_number }}</span>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <a href="{{ route('customers.show', $result->customer) }}" wire:navigate class="font-medium hover:underline">
                                            {{ $result->customer?->full_name ?? '-' }}
                                        </a>
                                    </flux:table.cell>
                                    <flux:table.cell>{{ format_rupiah($result->principal_amount) }}</flux:table.cell>
                                    <flux:table.cell>{{ format_rupiah($result->outstanding_total) }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge color="indigo">{{ str_replace('_', ' ', $result->status) }}</flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:button as="a" :href="route('loans.show', $result)" wire:navigate size="sm" variant="ghost">
                                            Lihat
                                        </flux:button>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @elseif ($selectedType === 'payment')
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Nomor Pembayaran</flux:table.column>
                            <flux:table.column>Nasabah</flux:table.column>
                            <flux:table.column>Nomor Pinjaman</flux:table.column>
                            <flux:table.column>Jumlah</flux:table.column>
                            <flux:table.column>Tanggal</flux:table.column>
                            <flux:table.column>Aksi</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($results as $result)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <span class="font-mono text-sm">{{ $result->payment_number }}</span>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <a href="{{ route('customers.show', $result->loan?->customer) }}" wire:navigate class="font-medium hover:underline">
                                            {{ $result->loan?->customer?->full_name ?? '-' }}
                                        </a>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <span class="font-mono text-sm">{{ $result->loan?->loan_number ?? '-' }}</span>
                                    </flux:table.cell>
                                    <flux:table.cell>{{ format_rupiah($result->amount) }}</flux:table.cell>
                                    <flux:table.cell>{{ $result->payment_date }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:button as="a" :href="route('payments.show', $result)" wire:navigate size="sm" variant="ghost">
                                            Lihat
                                        </flux:button>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @elseif ($selectedType === 'collateral')
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Kode Jaminan</flux:table.column>
                            <flux:table.column>Nasabah</flux:table.column>
                            <flux:table.column>Jenis</flux:table.column>
                            <flux:table.column>Nilai Taksir</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column>Aksi</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($results as $result)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <span class="font-mono text-sm">{{ $result->collateral_code }}</span>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <a href="{{ route('customers.show', $result->loan?->customer) }}" wire:navigate class="font-medium hover:underline">
                                            {{ $result->loan?->customer?->full_name ?? '-' }}
                                        </a>
                                    </flux:table.cell>
                                    <flux:table.cell>{{ $result->collateral_type }}</flux:table.cell>
                                    <flux:table.cell>{{ format_rupiah($result->estimated_value) }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge color="amber">{{ str_replace('_', ' ', $result->custody_status) }}</flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:button as="a" :href="route('collaterals.show', $result)" wire:navigate size="sm" variant="ghost">
                                            Lihat
                                        </flux:button>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @else
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Nomor Pelepasan</flux:table.column>
                            <flux:table.column>Nasabah</flux:table.column>
                            <flux:table.column>Kode Jaminan</flux:table.column>
                            <flux:table.column>Tanggal</flux:table.column>
                            <flux:table.column>Aksi</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($results as $result)
                                <flux:table.row>
                                    <flux:table.cell>
                                        <span class="font-mono text-sm">{{ $result->release_number }}</span>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <a href="{{ route('customers.show', $result->customer) }}" wire:navigate class="font-medium hover:underline">
                                            {{ $result->customer?->full_name ?? '-' }}
                                        </a>
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <span class="font-mono text-sm">{{ $result->collateral?->collateral_code ?? '-' }}</span>
                                    </flux:table.cell>
                                    <flux:table.cell>{{ $result->release_date }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:button as="a" :href="route('releases.show', $result)" wire:navigate size="sm" variant="ghost">
                                            Lihat
                                        </flux:button>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @endif

                @if ($results->hasPages())
                    <div class="mt-4">
                        {{ $results->links() }}
                    </div>
                @endif
            </flux:card>
        @endif
    </div>
</x-layouts::app>
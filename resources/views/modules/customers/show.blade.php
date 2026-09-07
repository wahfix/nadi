<x-layouts::app :title="$customer->full_name">
    @php
        $statusLabel = match ($customer->status) {
            'ACTIVE' => 'Aktif',
            'BLOCKED' => 'Diblokir',
            default => 'Nonaktif',
        };
        $statusColor = match ($customer->status) {
            'ACTIVE' => 'green',
            'BLOCKED' => 'red',
            default => 'neutral',
        };
        $auditActionLabels = [
            'CUSTOMER_CREATED' => 'Nasabah didaftarkan',
            'CUSTOMER_UPDATED' => 'Profil nasabah diubah',
        ];
    @endphp

    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('customers.index')" wire:navigate>Nasabah</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $customer->full_name }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        <x-flash-message type="success" :message="session('success')" />
        <x-flash-message type="error" :message="session('error')" />

        <flux:card>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <flux:avatar :name="$customer->full_name" :initials="\Illuminate\Support\Str::initials($customer->full_name)" size="xl" />
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <flux:heading size="xl">{{ $customer->full_name }}</flux:heading>
                            <flux:badge :color="$statusColor">{{ $statusLabel }}</flux:badge>
                        </div>
                        <flux:text class="font-mono text-sm text-neutral-500">{{ $customer->customer_code }}</flux:text>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @can('customers.edit')
                        <flux:button as="a" :href="route('customers.edit', $customer)" wire:navigate variant="primary" icon="pencil-square">
                            Ubah Data
                        </flux:button>
                    @endcan
                </div>
            </div>
        </flux:card>

        <div x-data="{ tab: 'ringkasan' }" class="flex flex-col gap-4">
            <div class="flex flex-wrap gap-1.5">
                @php
                    $tabs = [
                        'ringkasan' => 'Ringkasan',
                        'pinjaman' => 'Pinjaman',
                        'pembayaran' => 'Pembayaran',
                        'jaminan' => 'Jaminan',
                        'penagihan' => 'Penagihan',
                        'verifikasi' => 'Verifikasi',
                        'audit' => 'Audit',
                    ];
                @endphp

                @foreach ($tabs as $key => $label)
                    <button
                        type="button"
                        @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'bg-neutral-900 text-white' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'"
                        class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Ringkasan --}}
            <div x-show="tab === 'ringkasan'" x-cloak>
                <flux:card>
                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Tanggal Lahir</flux:text>
                            <flux:text>{{ format_date($customer->date_of_birth) }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">NIK</flux:text>
                            <flux:text>{{ $customer->nik_masked }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Jenis Kelamin</flux:text>
                            <flux:text>{{ $customer->gender === 'MALE' ? 'Laki-laki' : 'Perempuan' }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Nomor Telepon</flux:text>
                            <flux:text>{{ $customer->phone }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Alamat Email</flux:text>
                            <flux:text>{{ $customer->email ?? '-' }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Kota Domisili</flux:text>
                            <flux:text>{{ $customer->city }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1 md:col-span-2">
                            <flux:text variant="small" class="text-neutral-500">Alamat Domisili</flux:text>
                            <flux:text>{{ $customer->address }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Kontak Darurat</flux:text>
                            <flux:text>{{ $customer->emergency_contact_name }}</flux:text>
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:text variant="small" class="text-neutral-500">Telepon Kontak Darurat</flux:text>
                            <flux:text>{{ $customer->emergency_contact_phone }}</flux:text>
                        </div>
                    </div>
                </flux:card>

                <flux:card class="mt-4">
                    <flux:heading size="lg">Pekerjaan</flux:heading>
                    @if ($customer->activeEmployment)
                        <div class="mt-4 grid gap-6 md:grid-cols-2">
                            <div class="flex flex-col gap-1">
                                <flux:text variant="small" class="text-neutral-500">Perusahaan</flux:text>
                                <flux:text>{{ $customer->activeEmployment->company_name }}</flux:text>
                            </div>
                            <div class="flex flex-col gap-1">
                                <flux:text variant="small" class="text-neutral-500">Jabatan</flux:text>
                                <flux:text>{{ $customer->activeEmployment->position ?? '-' }}</flux:text>
                            </div>
                            <div class="flex flex-col gap-1">
                                <flux:text variant="small" class="text-neutral-500">Divisi</flux:text>
                                <flux:text>{{ $customer->activeEmployment->department ?? '-' }}</flux:text>
                            </div>
                            <div class="flex flex-col gap-1">
                                <flux:text variant="small" class="text-neutral-500">Jenis Pekerjaan</flux:text>
                                <flux:text>{{ $customer->activeEmployment->employment_type }}</flux:text>
                            </div>
                            <div class="flex flex-col gap-1">
                                <flux:text variant="small" class="text-neutral-500">Perkiraan Penghasilan Bulanan</flux:text>
                                <flux:text>{{ format_rupiah($customer->activeEmployment->estimated_monthly_income) }}</flux:text>
                            </div>
                            <div class="flex flex-col gap-1">
                                <flux:text variant="small" class="text-neutral-500">Status Pekerjaan</flux:text>
                                <flux:text>{{ $customer->activeEmployment->employment_status }}</flux:text>
                            </div>
                        </div>
                    @else
                        <div class="mt-4 flex flex-col items-center justify-center gap-2 p-8 text-center">
                            <flux:icon name="briefcase" variant="outline" class="size-8 text-neutral-400" />
                            <flux:text>Belum ada data pekerjaan untuk nasabah ini.</flux:text>
                        </div>
                    @endif
                </flux:card>
            </div>

            {{-- Pinjaman --}}
            <div x-show="tab === 'pinjaman'" x-cloak>
                <flux:card>
                    <flux:heading size="lg">Riwayat Pinjaman</flux:heading>
                    <div class="mt-4">
                        @if ($customer->loans->isEmpty())
                            <div class="flex flex-col items-center justify-center gap-2 p-8 text-center">
                                <flux:icon name="clipboard-document-list" variant="outline" class="size-8 text-neutral-400" />
                                <flux:text>Belum ada pinjaman untuk nasabah ini.</flux:text>
                            </div>
                        @endif
                    </div>
                </flux:card>
            </div>

            {{-- Pembayaran --}}
            <div x-show="tab === 'pembayaran'" x-cloak>
                <flux:card>
                    <flux:heading size="lg">Riwayat Pembayaran</flux:heading>
                    <div class="mt-4">
                        @if ($customer->payments->isEmpty())
                            <div class="flex flex-col items-center justify-center gap-2 p-8 text-center">
                                <flux:icon name="banknotes" variant="outline" class="size-8 text-neutral-400" />
                                <flux:text>Belum ada pembayaran untuk nasabah ini.</flux:text>
                            </div>
                        @endif
                    </div>
                </flux:card>
            </div>

            {{-- Jaminan --}}
            <div x-show="tab === 'jaminan'" x-cloak>
                <flux:card>
                    <flux:heading size="lg">Agunan / Jaminan</flux:heading>
                    <div class="mt-4">
                        @if ($customer->collaterals->isEmpty())
                            <div class="flex flex-col items-center justify-center gap-2 p-8 text-center">
                                <flux:icon name="wallet" variant="outline" class="size-8 text-neutral-400" />
                                <flux:text>Belum ada jaminan untuk nasabah ini.</flux:text>
                            </div>
                        @endif
                    </div>
                </flux:card>
            </div>

            {{-- Penagihan --}}
            <div x-show="tab === 'penagihan'" x-cloak>
                <flux:card>
                    <flux:heading size="lg">Aktivitas Penagihan</flux:heading>
                    <div class="mt-4">
                        @if ($customer->collectionActivities->isEmpty())
                            <div class="flex flex-col items-center justify-center gap-2 p-8 text-center">
                                <flux:icon name="hand-raised" variant="outline" class="size-8 text-neutral-400" />
                                <flux:text>Belum ada aktivitas penagihan untuk nasabah ini.</flux:text>
                            </div>
                        @endif
                    </div>
                </flux:card>
            </div>

            {{-- Verifikasi --}}
            <div x-show="tab === 'verifikasi'" x-cloak>
                <flux:card>
                    <flux:heading size="lg">Verifikasi Identitas</flux:heading>
                    <div class="mt-4">
                        @if ($customer->identityVerifications->isEmpty())
                            <div class="flex flex-col items-center justify-center gap-2 p-8 text-center">
                                <flux:icon name="shield-check" variant="outline" class="size-8 text-neutral-400" />
                                <flux:text>Belum ada riwayat verifikasi identitas untuk nasabah ini.</flux:text>
                            </div>
                        @endif
                    </div>
                </flux:card>
            </div>

            {{-- Audit --}}
            <div x-show="tab === 'audit'" x-cloak>
                <flux:card>
                    <flux:heading size="lg">Jejak Audit Profil</flux:heading>
                    <div class="mt-4">
                        @if ($auditLogs->isEmpty())
                            <div class="flex flex-col items-center justify-center gap-2 p-8 text-center">
                                <flux:icon name="document-magnifying-glass" variant="outline" class="size-8 text-neutral-400" />
                                <flux:text>Belum ada catatan audit untuk profil nasabah ini.</flux:text>
                            </div>
                        @else
                            <flux:table>
                                <flux:table.columns>
                                    <flux:table.column>Waktu</flux:table.column>
                                    <flux:table.column>Aksi</flux:table.column>
                                    <flux:table.column>Petugas</flux:table.column>
                                </flux:table.columns>
                                <flux:table.rows>
                                    @foreach ($auditLogs as $log)
                                        <flux:table.row>
                                            <flux:table.cell>{{ format_date_indonesian($log->created_at, true) }}</flux:table.cell>
                                            <flux:table.cell>{{ $auditActionLabels[$log->action] ?? $log->action }}</flux:table.cell>
                                            <flux:table.cell>{{ $log->user?->name ?? 'Sistem' }}</flux:table.cell>
                                        </flux:table.row>
                                    @endforeach
                                </flux:table.rows>
                            </flux:table>
                        @endif
                    </div>
                </flux:card>
            </div>
        </div>
    </div>
</x-layouts::app>
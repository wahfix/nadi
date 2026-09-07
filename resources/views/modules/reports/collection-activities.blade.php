<x-report-frame :title="'Laporan Aktivitas Penagihan'" subtitle="Riwayat penagihan, metode kontak, hasil, dan janji bayar nasabah." :reportName="'Aktivitas Penagihan'">
    <x-slot name="filters">
        <form method="GET" action="{{ route('reports.collection-activities') }}" class="mb-4 flex flex-wrap items-end gap-3">
            <div class="min-w-64 flex-1">
                <flux:input
                    type="search"
                    name="search"
                    :value="request('search')"
                    placeholder="Cari nama nasabah..."
                />
            </div>

            <div>
                <flux:input type="date" name="date_from" :value="request('date_from')" label="Dari Tanggal" />
            </div>

            <div>
                <flux:input type="date" name="date_to" :value="request('date_to')" label="Sampai Tanggal" />
            </div>

            <flux:select name="method" class="w-52">
                <flux:select.option value="">Semua Metode</flux:select.option>
                @foreach ($methodLabels as $value => $label)
                    <flux:select.option :value="$value" :selected="request('method') == $value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select name="result" class="w-52">
                <flux:select.option value="">Semua Hasil</flux:select.option>
                @foreach ($resultLabels as $value => $label)
                    <flux:select.option :value="$value" :selected="request('result') == $value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:button variant="primary" type="submit">Filter</flux:button>

            @if (request()->hasAny(['search', 'date_from', 'date_to', 'method', 'result']))
                <flux:button as="a" :href="route('reports.collection-activities')" wire:navigate variant="ghost">Reset</flux:button>
            @endif
        </form>
    </x-slot>

    @if ($activities->isEmpty())
        <div class="flex flex-col items-center justify-center gap-3 p-10 text-center">
            <flux:icon name="phone" variant="outline" class="size-10 text-neutral-400" />
            <flux:heading size="lg">Tidak ada aktivitas penagihan.</flux:heading>
            <flux:text>Belum ada aktivitas yang sesuai dengan filter ini.</flux:text>
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Tanggal</flux:table.column>
                <flux:table.column>Nasabah</flux:table.column>
                <flux:table.column>No. Pinjaman</flux:table.column>
                <flux:table.column>Metode</flux:table.column>
                <flux:table.column>Hasil</flux:table.column>
                <flux:table.column>Janji Bayar</flux:table.column>
                <flux:table.column>Petugas</flux:table.column>
                <flux:table.column>Catatan</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($activities as $activity)
                    <flux:table.row>
                        <flux:table.cell>{{ format_date($activity->contact_date) }}</flux:table.cell>
                        <flux:table.cell class="font-medium">{{ $activity->loan?->customer?->full_name ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="font-mono text-xs">{{ $activity->loan?->loan_number ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $methodLabels[$activity->contact_method] ?? $activity->contact_method }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$activity->result === 'PAID' || $activity->result === 'PROMISE_TO_PAY' ? 'green' : 'amber'">{{ $resultLabels[$activity->result] ?? $activity->result }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">
                            @if ($activity->promise_to_pay_date)
                                {{ format_date($activity->promise_to_pay_date) }} • {{ format_rupiah($activity->promise_to_pay_amount) }}
                            @else
                                —
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $activity->collector?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="max-w-56 truncate">{{ $activity->notes }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="pagination-links mt-4">{{ $activities->links() }}</div>
    @endif
</x-report-frame>
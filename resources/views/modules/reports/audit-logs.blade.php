<x-report-frame :title="'Laporan Audit Log'" subtitle="Jejak audit seluruh mutasi data dalam rentang tanggal pada satu rentang waktu." :reportName="'Audit Log'">
    <x-slot name="filters">
        <form method="GET" action="{{ route('reports.audit-logs') }}" class="mb-4 flex flex-wrap items-end gap-3">
            <div class="min-w-64 flex-1">
                <flux:input
                    type="search"
                    name="search"
                    :value="request('search')"
                    placeholder="Cari kode aksi..."
                />
            </div>

            <div>
                <flux:input type="date" name="date_from" :value="request('date_from')" label="Dari Tanggal" />
            </div>

            <div>
                <flux:input type="date" name="date_to" :value="request('date_to')" label="Sampai Tanggal" />
            </div>

            <flux:select name="action" class="w-64">
                <flux:select.option value="">Semua Aksi</flux:select.option>
                @foreach ($actions as $action)
                    <flux:select.option :value="$action" :selected="request('action') == $action">{{ $action }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:button variant="primary" type="submit">Filter</flux:button>

            @if (request()->hasAny(['search', 'date_from', 'date_to', 'action']))
                <flux:button as="a" :href="route('reports.audit-logs')" wire:navigate variant="ghost">Reset</flux:button>
            @endif
        </form>
    </x-slot>

    @if ($logs->isEmpty())
        <div class="flex flex-col items-center justify-center gap-3 p-10 text-center">
            <flux:icon name="document-magnifying-glass" variant="outline" class="size-10 text-neutral-400" />
            <flux:heading size="lg">Tidak ada catatan audit.</flux:heading>
            <flux:text>Belum ada event audit yang sesuai dengan filter ini.</flux:text>
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Waktu</flux:table.column>
                <flux:table.column>Pengguna</flux:table.column>
                <flux:table.column>Aksi</flux:table.column>
                <flux:table.column>Modul</flux:table.column>
                <flux:table.column>Record</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($logs as $log)
                    <flux:table.row>
                        <flux:table.cell class="whitespace-nowrap">{{ format_date_indonesian($log->created_at, true) }}</flux:table.cell>
                        <flux:table.cell>{{ $log->user?->name ?? 'Sistem' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge variant="outline">{{ $log->action }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ class_basename($log->entity_type) }}</flux:table.cell>
                        <flux:table.cell class="font-mono text-xs">ID {{ $log->entity_id }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        <div class="pagination-links mt-4">{{ $logs->links() }}</div>
    @endif
</x-report-frame>
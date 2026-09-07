<x-layouts::app :title="__('Audit Log')">
    <div class="flex flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1.5">
            <flux:heading size="xl">Audit Log</flux:heading>
            <flux:subheading>Jejak audit permanen seluruh mutasi data sistem dengan perbandingan sebelum dan sesudah.</flux:subheading>
        </div>

        <x-flash-message type="success" :message="session('success')" />
        <x-flash-message type="error" :message="session('error')" />

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
            Catatan audit bersifat permanen dan tidak dapat dihapus atau dimanipulasi melalui aplikasi.
        </div>

        <flux:card x-data="{ diff: null }">
            <form method="GET" action="{{ route('audit-log.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
                <div class="min-w-64 flex-1">
                    <flux:input
                        type="search"
                        name="search"
                        :value="request('search')"
                        :placeholder="__('Cari aksi, modul, atau nama/email pengguna...')"
                    />
                </div>

                <flux:select name="action" class="w-56">
                    <flux:select.option value="">Semua Aksi</flux:select.option>
                    @foreach ($actionOptions as $action)
                        <flux:select.option :value="$action" :selected="request('action') == $action">{{ $action }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select name="entity" class="w-52">
                    <flux:select.option value="">Semua Modul</flux:select.option>
                    @foreach ($entityOptions as $class => $label)
                        <flux:select.option :value="$class" :selected="request('entity') == $class">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div>
                    <flux:input type="date" name="date_from" :value="request('date_from')" label="Dari Tanggal" />
                </div>
                <div>
                    <flux:input type="date" name="date_to" :value="request('date_to')" label="Sampai Tanggal" />
                </div>

                <flux:button variant="primary" type="submit">Filter</flux:button>

                @if (request()->hasAny(['search', 'action', 'entity', 'date_from', 'date_to']))
                    <flux:button as="a" :href="route('audit-log.index')" wire:navigate variant="ghost">Reset</flux:button>
                @endif
            </form>

            @if ($logs->isEmpty())
                <div class="flex flex-col items-center justify-center gap-3 p-10 text-center">
                    <flux:icon name="document-magnifying-glass" variant="outline" class="size-10 text-neutral-400" />
                    <flux:heading size="lg">Tidak ada catatan audit ditemukan.</flux:heading>
                    <flux:text>Tidak ada event audit yang cocok dengan kriteria pencarian ini.</flux:text>
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Waktu</flux:table.column>
                        <flux:table.column>Pengguna</flux:table.column>
                        <flux:table.column>Aksi</flux:table.column>
                        <flux:table.column>Modul</flux:table.column>
                        <flux:table.column>Record</flux:table.column>
                        <flux:table.column>Perubahan</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($logs as $log)
                            <flux:table.row>
                                <flux:table.cell class="whitespace-nowrap">{{ format_date_indonesian($log->created_at, true) }}</flux:table.cell>
                                <flux:table.cell>
                                    <p class="font-medium">{{ $log->user?->name ?? 'Sistem' }}</p>
                                    <p class="text-xs text-neutral-500">{{ $log->user?->email ?? 'otomatis' }}</p>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge :color="$actionColor($log->action)">{{ $log->action }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>{{ $entityLabel($log->entity_type) }}</flux:table.cell>
                                <flux:table.cell>
                                    <span class="font-mono text-xs">{{ $recordRef($log->entity_type, (int) $log->entity_id) }} (ID {{ $log->entity_id }})</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($log->old_values || $log->new_values)
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            @click="diff = { before: @js($log->old_values), after: @js($log->new_values) }"
                                        >
                                            Lihat Diff
                                        </flux:button>
                                    @else
                                        <span class="text-neutral-400">—</span>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <div class="mt-4">
                    {{ $logs->links() }}
                </div>
            @endif

            <div
                x-show="diff"
                x-cloak
                x-transition
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            >
                <div class="grid w-full max-w-4xl grid-cols-1 gap-4 rounded-2xl bg-white p-6 shadow-2xl sm:grid-cols-2 dark:bg-zinc-900">
                    <div>
                        <div class="mb-2 flex items-center gap-2">
                            <span class="rounded-md bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">BEFORE</span>
                            <span class="text-sm text-neutral-500">Kondisi Sebelum</span>
                        </div>
                        <pre class="max-h-[70vh] overflow-auto rounded-lg bg-neutral-50 p-3 text-xs leading-relaxed dark:bg-zinc-800" x-text="JSON.stringify(diff?.before, null, 2)"></pre>
                    </div>
                    <div>
                        <div class="mb-2 flex items-center gap-2">
                            <span class="rounded-md bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">AFTER</span>
                            <span class="text-sm text-neutral-500">Kondisi Sesudah</span>
                        </div>
                        <pre class="max-h-[70vh] overflow-auto rounded-lg bg-neutral-50 p-3 text-xs leading-relaxed dark:bg-zinc-800" x-text="JSON.stringify(diff?.after, null, 2)"></pre>
                    </div>
                    <div class="flex justify-end sm:col-span-2">
                        <flux:button variant="primary" @click="diff = null">Tutup</flux:button>
                    </div>
                </div>
            </div>
        </flux:card>
    </div>
</x-layouts::app>
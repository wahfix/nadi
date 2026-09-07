<div class="flex flex-col gap-6">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-stat-card icon="chart-bar" label="Event Audit Hari Ini" :value="$dashboard['today_events']" accent="blue" />
        <x-stat-card icon="chart-bar" label="Total Event 7 Hari" :value="array_sum($dashboard['trend'])" accent="green" />
    </div>

    <flux:card>
        <flux:heading size="lg">Tren Aktivitas 7 Hari Terakhir</flux:heading>
        <flux:subheading>Jumlah mutasi audit log per hari.</flux:subheading>

        @php
            $maxTrend = max(1, max($dashboard['trend']));
        @endphp
        <div class="mt-4 flex items-end justify-between gap-2 sm:gap-4">
            @foreach ($dashboard['trend'] as $label => $count)
                <div class="flex flex-1 flex-col items-center gap-2">
                    <span class="text-xs font-semibold text-neutral-600 dark:text-neutral-300">{{ $count }}</span>
                    <div
                        class="w-full rounded-t-md bg-blue-500/80 transition-all"
                        style="height: {{ max(4, (int) round($count * 100 / $maxTrend)) }}px"
                    ></div>
                    <span class="text-xs text-neutral-500">{{ $label }}</span>
                </div>
            @endforeach
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Mutasi Audit Terbaru</flux:heading>
        <flux:subheading>10 event terminal yang baru tercatat.</flux:subheading>

        @if ($dashboard['recent']->isEmpty())
            <div class="flex flex-col items-center justify-center gap-3 p-8 text-center">
                <flux:icon name="document-magnifying-glass" variant="outline" class="size-8 text-neutral-400" />
                <flux:text>Belum ada event audit.</flux:text>
            </div>
        @else
            <flux:table class="mt-4">
                <flux:table.columns>
                    <flux:table.column>Waktu</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                    <flux:table.column>Entitas</flux:table.column>
                    <flux:table.column>Oleh</flux:table.column>
                    <flux:table.column>IP</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($dashboard['recent'] as $log)
                        <flux:table.row>
                            <flux:table.cell class="whitespace-nowrap">{{ format_date_indonesian($log->created_at, true) }}</flux:table.cell>
                            <flux:table.cell><code class="rounded bg-neutral-100 px-1.5 py-0.5 text-xs dark:bg-neutral-800">{{ $log->action }}</code></flux:table.cell>
                            <flux:table.cell class="text-xs">{{ $log->entity_type }}</flux:table.cell>
                            <flux:table.cell>{{ $log->user?->name ?? '-' }}</flux:table.cell>
                            <flux:table.cell class="font-mono text-xs">{{ $log->ip_address ?? '-' }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>

    @can('reports.view')
        <div class="flex gap-3">
            <flux:button as="a" :href="route('reports.index')" wire:navigate variant="primary" icon="chart-bar">
                Buka Laporan
            </flux:button>
        </div>
    @endcan
</div>
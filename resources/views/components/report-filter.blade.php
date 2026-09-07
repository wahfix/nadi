@props([
    'route' => null,
    'statusParam' => 'status',
    'statusOptions' => null,
    'searchLabel' => null,
])

<form method="GET" action="{{ $route }}" class="mb-4 flex flex-wrap items-end gap-3">
    @if ($searchLabel)
        <div class="min-w-64 flex-1">
            <flux:input
                type="search"
                name="search"
                :value="request('search')"
                :placeholder="__($searchLabel)"
            />
        </div>
    @endif

    <div>
        <flux:input type="date" name="date_from" :value="request('date_from')" label="Dari Tanggal" />
    </div>

    <div>
        <flux:input type="date" name="date_to" :value="request('date_to')" label="Sampai Tanggal" />
    </div>

    @if ($statusOptions)
        <flux:select name="{{ $statusParam }}" class="w-56">
            <flux:select.option value="">Semua Status</flux:select.option>
            @foreach ($statusOptions as $value => $label)
                <flux:select.option :value="$value" :selected="request($statusParam) == $value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    @endif

    <flux:button variant="primary" type="submit">Filter</flux:button>

    @if (request()->hasAny(['search', 'date_from', 'date_to', $statusParam]))
        <flux:button as="a" :href="$route" wire:navigate variant="ghost">Reset</flux:button>
    @endif
</form>
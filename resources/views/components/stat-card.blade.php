@props(['icon' => 'circle-check', 'label' => '', 'value' => '', 'meta' => '', 'accent' => 'neutral'])

@php
    $accentStyles = [
        'neutral' => 'border-neutral-200 bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800/50',
        'blue' => 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/30',
        'amber' => 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/30',
        'red' => 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/30',
        'green' => 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/30',
    ];
    $iconStyles = [
        'neutral' => 'text-neutral-500',
        'blue' => 'text-blue-500',
        'amber' => 'text-amber-500',
        'red' => 'text-red-500',
        'green' => 'text-emerald-500',
    ];
@endphp

<flux:card>
    <div class="flex items-center gap-3">
        <div class="rounded-lg p-2 {{ $accentStyles[$accent] ?? $accentStyles['neutral'] }}">
            <flux:icon :name="$icon" variant="outline" class="size-5 {{ $iconStyles[$accent] ?? $iconStyles['neutral'] }}" />
        </div>
        <div class="min-w-0">
            <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ $label }}</p>
            <p class="truncate text-xl font-semibold">{{ $value }}</p>
            @if ($meta)
                <p class="truncate text-xs text-neutral-500 dark:text-neutral-400">{{ $meta }}</p>
            @endif
        </div>
    </div>
</flux:card>
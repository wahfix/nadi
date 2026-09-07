@props(['type' => 'success', 'message'])

@if ($message)
    <div
        x-data="{ visible: true }"
        x-init="setTimeout(() => visible = false, 4000)"
        x-show="visible"
        x-transition:leave="transition-opacity duration-500"
        x-transition:leave-end="opacity-0"
        role="alert"
        @class([
            'mb-4 flex items-center gap-2 rounded-lg border px-4 py-3 text-sm font-medium',
            'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300' => $type === 'success',
            'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-300' => $type === 'error',
        ])
    >
        <flux:icon :name="$type === 'success' ? 'check-circle' : 'exclamation-triangle'" class="size-4 shrink-0" />
        <span>{{ $message }}</span>
        <button type="button" class="ms-auto text-current/70 hover:text-current" @click="visible = false" aria-label="Tutup">
            <flux:icon name="x-mark" class="size-4" />
        </button>
    </div>
@endif
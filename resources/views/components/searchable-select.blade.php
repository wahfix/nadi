@props([
    'name' => '',
    'id' => null,
    'label' => null,
    'options' => [],
    'selected' => '',
    'placeholder' => 'Ketik untuk mencari…',
    'required' => false,
    'autoSubmit' => false,
    'hint' => null,
    'emptyText' => 'Tidak ada hasil yang cocok',
])

@php
    $inputId = $id ?? 'search-' . $name;
    $selectedInit = (string) $selected;
    $optionsJson = Illuminate\Support\Js::from(collect($options)->map(fn ($o) => [
        'id' => (string) ($o['id'] ?? $o->id ?? ''),
        'label' => (string) ($o['label'] ?? $o->label ?? ''),
        'sublabel' => (string) ($o['sublabel'] ?? ''),
        'badge' => (string) ($o['badge'] ?? ''),
        'search' => (string) ($o['search'] ?? ''),
    ])->values()->all());
@endphp

<div
    x-data="{
        open: false,
        query: '',
        value: '{{ $selectedInit }}',
        label: '',
        activeIndex: 0,
        options: {{ $optionsJson }},

        init() {
            const picked = this.options.find((o) => String(o.id) === String(this.value));
            if (picked) this.label = picked.label;
        },

        get filtered() {
            const q = this.query.trim().toLowerCase();
            if (!q) return this.options;
            return this.options.filter((o) => {
                const haystack = (o.label + ' ' + o.sublabel + ' ' + o.search).toLowerCase();
                return haystack.includes(q);
            });
        },

        openList() {
            this.open = true;
            this.activeIndex = 0;
        },

        pick(option) {
            this.value = String(option.id);
            this.label = option.label;
            this.query = option.label;
            this.open = false;
            this.$dispatch('searchable-select:change', { name: '{{ $name }}', value: this.value });
            if (@js($autoSubmit)) {
                this.$el.closest('form')?.submit();
            }
        },

        clear() {
            this.value = '';
            this.label = '';
            this.query = '';
            this.open = false;
            this.$dispatch('searchable-select:change', { name: '{{ $name }}', value: '' });
        },

        onKeydown(event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (!this.open) this.openList();
                this.activeIndex = Math.min(this.activeIndex + 1, this.filtered.length - 1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                this.activeIndex = Math.max(this.activeIndex - 1, 0);
            } else if (event.key === 'Enter') {
                if (this.open && this.filtered[this.activeIndex]) {
                    event.preventDefault();
                    this.pick(this.filtered[this.activeIndex]);
                }
            } else if (event.key === 'Escape') {
                this.open = false;
            }
        },
    }"
    class="relative w-full"
    @click.outside="open = false"
>
    <input type="hidden" name="{{ $name }}" :value="value" {{ $required ? 'required' : '' }} />

    @if ($label)
        <label for="{{ $inputId }}" class="mb-1.5 block text-sm font-medium text-neutral-700 dark:text-neutral-200">
            {{ $label }} @if ($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif

    <div class="relative">
        <input
            type="text"
            id="{{ $inputId }}"
            role="combobox"
            aria-autocomplete="list"
            :aria-expanded="open ? 'true' : 'false'"
            :value="query"
            @input="query = $event.target.value; open = true; activeIndex = 0"
            @focus="openList()"
            @keydown="onKeydown($event)"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 pr-9 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-200 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100 dark:placeholder:text-neutral-500 dark:focus:border-neutral-400 dark:focus:ring-neutral-600"
        />

        <button
            type="button"
            @click.prevent="value ? clear() : openList()"
            class="absolute inset-y-0 right-0 flex items-center pr-3 text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-200"
            :aria-label="value ? 'Hapus pilihan' : 'Buka daftar'"
        >
            <template x-if="value">
                <flux:icon name="x-mark" class="size-4" />
            </template>
            <template x-if="!value">
                <flux:icon name="chevron-up-down" class="size-4" />
            </template>
        </button>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="absolute z-50 mt-1.5 max-h-64 w-full overflow-y-auto rounded-lg border border-neutral-200 bg-white py-1 shadow-lg dark:border-neutral-700 dark:bg-neutral-800"
            role="listbox"
        >
            <template x-if="filtered.length === 0">
                <p class="px-3 py-2.5 text-sm text-neutral-500 dark:text-neutral-400">{{ $emptyText }}</p>
            </template>

            <template x-for="(option, index) in filtered" :key="option.id">
                <button
                    type="button"
                    role="option"
                    :aria-selected="String(option.id) === String(value)"
                    @click="pick(option)"
                    @mouseenter="activeIndex = index"
                    :class="activeIndex === index ? 'bg-neutral-100 dark:bg-neutral-700' : ''"
                    class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm"
                >
                    <span class="min-w-0">
                        <span x-text="option.label" class="block truncate font-medium text-neutral-900 dark:text-neutral-100"></span>
                        <span x-show="option.sublabel" x-text="option.sublabel" class="block truncate text-xs text-neutral-500 dark:text-neutral-400"></span>
                    </span>
                    <span
                        x-show="option.badge"
                        x-text="option.badge"
                        class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
                    ></span>
                </button>
            </template>
        </div>
    </div>

    @if ($hint)
        <p class="mt-1.5 text-xs text-neutral-500 dark:text-neutral-400">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
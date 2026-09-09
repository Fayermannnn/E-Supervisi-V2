@props([
    'label' => null,
    'name',
    'type' => 'text',
    'hint' => null,
])

<div class="space-y-1.5">
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-ink-800 dark:text-ink-100">{{ $label }}</label>
    @endif

    <input
        type="{{ $type }}"
        id="{{ $name }}"
        {{ $attributes->merge([
            'class' => 'block w-full rounded-md border-0 bg-[var(--surface)] px-3 py-2 text-sm text-ink-900 dark:text-ink-50 ring-1 ring-inset ring-[var(--border)] placeholder:text-ink-400 focus:ring-2 focus:ring-inset focus:ring-brand-600',
        ]) }}
    />

    @if ($hint)
        <p class="text-xs text-[var(--text-muted)]">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="text-xs text-status-overdue">{{ $message }}</p>
    @enderror
</div>

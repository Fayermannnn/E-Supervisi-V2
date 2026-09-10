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
        {{ $attributes->merge(['class' => 'field-input']) }}
    />

    @if ($hint)
        <p class="text-xs text-[var(--text-muted)]">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="text-xs font-medium text-status-overdue">{{ $message }}</p>
    @enderror
</div>

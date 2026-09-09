@props(['label', 'value', 'tone' => 'default', 'href' => null])

@php
    $toneClass = [
        'default' => 'text-ink-900 dark:text-ink-50',
        'warning' => 'text-status-progress',
        'danger' => 'text-status-overdue',
        'success' => 'text-status-done',
    ][$tone] ?? 'text-ink-900';

    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" wire:navigate @endif
    {{ $attributes->merge(['class' => 'block rounded-xl border border-[var(--border)] bg-[var(--surface)] px-5 py-4 shadow-sm' . ($href ? ' transition hover:border-brand-300 hover:shadow' : '')]) }}>
    <dt class="text-xs font-medium uppercase tracking-wide text-[var(--text-muted)]">{{ $label }}</dt>
    <dd class="mt-1 text-2xl font-semibold {{ $toneClass }}">{{ $value }}</dd>
    @isset($footer)
        <p class="mt-1 text-xs text-[var(--text-muted)]">{{ $footer }}</p>
    @endisset
</{{ $tag }}>

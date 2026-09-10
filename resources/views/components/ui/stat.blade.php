@props(['label', 'value', 'tone' => 'default', 'href' => null, 'hint' => null])

@php
    $tones = [
        'default' => ['num' => 'text-ink-900 dark:text-ink-50', 'bar' => 'text-brand-600'],
        'warning' => ['num' => 'text-status-progress', 'bar' => 'text-status-progress'],
        'danger' => ['num' => 'text-status-overdue', 'bar' => 'text-status-overdue'],
        'success' => ['num' => 'text-status-done', 'bar' => 'text-status-done'],
    ];
    $t = $tones[$tone] ?? $tones['default'];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" wire:navigate @endif
    {{ $attributes->merge([
        'class' => 'accent-bar '.$t['bar'].' group block rounded-xl border border-[var(--border)] bg-[var(--surface)] pl-5 pr-4 py-4 shadow-xs'
            .($href ? ' transition hover:border-brand-300 hover:shadow-sm' : ''),
    ]) }}>
    <dt class="text-[0.7rem] font-semibold uppercase tracking-[0.06em] text-[var(--text-muted)]">{{ $label }}</dt>
    <dd class="mt-1 flex items-baseline gap-2">
        <span class="text-[1.7rem] font-bold leading-none {{ $t['num'] }}">{{ $value }}</span>
        @if ($href)
            <span aria-hidden="true" class="ml-auto translate-x-0 text-ink-300 transition group-hover:translate-x-0.5 group-hover:text-brand-500">→</span>
        @endif
    </dd>
    @if ($hint)
        <p class="mt-1.5 text-xs text-[var(--text-muted)]">{{ $hint }}</p>
    @endif
    @isset($footer)
        <p class="mt-1.5 text-xs text-[var(--text-muted)]">{{ $footer }}</p>
    @endisset
</{{ $tag }}>

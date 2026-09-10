@props(['title' => 'Belum ada data', 'description' => null, 'icon' => 'inbox'])

@php
    $paths = [
        'inbox' => '<path d="M3 12h4l2 3h6l2-3h4M3 12l2.5-7A2 2 0 0 1 7.4 4h9.2a2 2 0 0 1 1.9 1.3L21 12v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5Z"/>',
        'chart' => '<path d="M4 19V5M4 19h16M9 15V9m5 6V6m5 9v-3"/>',
        'clipboard' => '<path d="M9 4h6v3H9zM7 5h1M16 5h1a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h1"/>',
    ][$icon] ?? '<path d="M3 12h4l2 3h6l2-3h4M3 12l2.5-7A2 2 0 0 1 7.4 4h9.2a2 2 0 0 1 1.9 1.3L21 12v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5Z"/>';
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border border-dashed border-[var(--border-strong)] bg-[var(--surface)] px-6 py-12 text-center']) }}>
    <span class="mx-auto flex size-11 items-center justify-center rounded-full bg-brand-50 text-brand-700 dark:bg-brand-950/50 dark:text-brand-300">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">{!! $paths !!}</svg>
    </span>
    <p class="mt-3 text-sm font-semibold text-ink-800 dark:text-ink-100">{{ $title }}</p>
    @if ($description)
        <p class="mx-auto mt-1 max-w-sm text-sm text-[var(--text-muted)]">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-4 flex justify-center">{{ $action }}</div>
    @endisset
</div>

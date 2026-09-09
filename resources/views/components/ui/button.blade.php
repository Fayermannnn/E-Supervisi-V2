@props([
    'variant' => 'primary',
    'type' => 'button',
    'as' => 'button',
])

@php
    $classes = [
        'primary' => 'bg-brand-600 text-white hover:bg-brand-700 focus-visible:outline-brand-600',
        'secondary' => 'bg-[var(--surface)] text-ink-800 dark:text-ink-100 ring-1 ring-inset ring-[var(--border)] hover:bg-ink-50 dark:hover:bg-ink-800',
        'danger' => 'bg-status-overdue text-white hover:opacity-90 focus-visible:outline-status-overdue',
        'ghost' => 'text-ink-600 dark:text-ink-300 hover:bg-ink-100 dark:hover:bg-ink-800',
    ][$variant] ?? '';

    $base = 'inline-flex items-center justify-center gap-2 rounded-md px-3.5 py-2 text-sm font-medium transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:opacity-50 disabled:pointer-events-none';
@endphp

@if ($as === 'a')
    <a {{ $attributes->merge(['class' => "$base $classes"]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => "$base $classes"]) }}>{{ $slot }}</button>
@endif

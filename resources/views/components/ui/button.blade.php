@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'as' => 'button',
    'icon' => false,
])

@php
    $variants = [
        'primary' => 'bg-brand-700 text-white shadow-xs hover:bg-brand-800 active:bg-brand-900',
        'secondary' => 'bg-[var(--surface)] text-ink-800 dark:text-ink-100 ring-1 ring-inset ring-[var(--border-strong)] shadow-xs hover:bg-ink-50 dark:hover:bg-ink-800',
        'danger' => 'bg-status-overdue text-white shadow-xs hover:brightness-95 active:brightness-90',
        'ghost' => 'text-ink-600 dark:text-ink-300 hover:bg-ink-100 dark:hover:bg-ink-800',
        'link' => 'text-brand-700 dark:text-brand-300 hover:underline underline-offset-2 px-0 py-0',
    ];
    $classes = $variants[$variant] ?? $variants['primary'];

    $sizes = [
        'sm' => 'gap-1.5 px-2.5 py-1.5 text-xs rounded-md',
        'md' => 'gap-2 px-3.5 py-2 text-sm rounded-lg',
        'lg' => 'gap-2 px-4 py-2.5 text-sm rounded-lg',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    if ($icon) {
        $sizeClass = ['sm' => 'p-1.5 rounded-md', 'md' => 'p-2 rounded-lg', 'lg' => 'p-2.5 rounded-lg'][$size] ?? 'p-2 rounded-lg';
    }

    $base = 'relative inline-flex items-center justify-center font-medium transition disabled:opacity-50 disabled:pointer-events-none';
    $cls = trim("$base $sizeClass $classes");
@endphp

@if ($as === 'a')
    <a {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</button>
@endif

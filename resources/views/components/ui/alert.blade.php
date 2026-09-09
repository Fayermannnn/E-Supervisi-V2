@props(['variant' => 'info', 'title' => null])

@php
    $tone = [
        'info' => 'bg-brand-50 text-brand-900 ring-brand-200 dark:bg-brand-900/30 dark:text-brand-100 dark:ring-brand-700',
        'success' => 'bg-status-done/10 text-status-done ring-status-done/20',
        'warning' => 'bg-status-progress/10 text-status-progress ring-status-progress/20',
        'danger' => 'bg-status-overdue/10 text-status-overdue ring-status-overdue/20',
    ][$variant] ?? '';
@endphp

<div role="alert" {{ $attributes->merge(['class' => "rounded-md px-3.5 py-3 text-sm ring-1 ring-inset $tone"]) }}>
    @if ($title)
        <p class="font-semibold">{{ $title }}</p>
    @endif
    <div class="{{ $title ? 'mt-1' : '' }}">{{ $slot }}</div>
</div>

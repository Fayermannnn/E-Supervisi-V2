@props(['variant' => 'info', 'title' => null])

@php
    $tone = [
        'info' => ['wrap' => 'bg-brand-50 text-brand-950 ring-brand-200 dark:bg-brand-950/40 dark:text-brand-50 dark:ring-brand-800', 'icon' => 'i'],
        'success' => ['wrap' => 'bg-status-done/10 text-status-done ring-status-done/25', 'icon' => '✓'],
        'warning' => ['wrap' => 'bg-status-progress/10 text-status-progress ring-status-progress/25', 'icon' => '!'],
        'danger' => ['wrap' => 'bg-status-overdue/10 text-status-overdue ring-status-overdue/25', 'icon' => '!'],
    ][$variant] ?? [];
@endphp

<div role="alert" {{ $attributes->merge(['class' => "flex gap-3 rounded-lg px-3.5 py-3 text-sm ring-1 ring-inset {$tone['wrap']}"]) }}>
    <span class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full bg-current text-[10px] font-bold text-[var(--surface)]" aria-hidden="true">{{ $tone['icon'] }}</span>
    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div class="{{ $title ? 'mt-0.5' : '' }} [&_a]:font-medium [&_a]:underline [&_a]:underline-offset-2">{{ $slot }}</div>
    </div>
</div>

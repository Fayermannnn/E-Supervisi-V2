@props(['status' => 'draft', 'label' => null])

@php
    $tone = [
        'draft' => 'bg-status-draft/10 text-status-draft ring-status-draft/20',
        'scheduled' => 'bg-status-scheduled/10 text-status-scheduled ring-status-scheduled/20',
        'progress' => 'bg-status-progress/10 text-status-progress ring-status-progress/20',
        'done' => 'bg-status-done/10 text-status-done ring-status-done/20',
        'overdue' => 'bg-status-overdue/10 text-status-overdue ring-status-overdue/25',
        'archived' => 'bg-status-archived/10 text-status-archived ring-status-archived/20',
        'canceled' => 'bg-status-archived/10 text-status-archived ring-status-archived/20 line-through',
    ][$status] ?? 'bg-ink-100 text-ink-600 ring-ink-200';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 whitespace-nowrap rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset $tone"]) }}>
    <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
    {{ $label ?? $slot }}
</span>

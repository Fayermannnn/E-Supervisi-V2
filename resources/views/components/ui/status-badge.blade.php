@props(['status' => 'draft', 'label' => null])

@php
    $tone = [
        'draft' => 'bg-status-draft/12 text-status-draft',
        'scheduled' => 'bg-status-scheduled/12 text-status-scheduled',
        'progress' => 'bg-status-progress/12 text-status-progress',
        'done' => 'bg-status-done/12 text-status-done',
        'overdue' => 'bg-status-overdue/12 text-status-overdue',
        'archived' => 'bg-status-archived/12 text-status-archived',
        'canceled' => 'bg-status-archived/12 text-status-archived line-through',
    ][$status] ?? 'bg-ink-100 text-ink-600';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium $tone"]) }}>
    <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
    {{ $label ?? $slot }}
</span>

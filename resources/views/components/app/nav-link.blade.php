@props(['href', 'icon' => null, 'disabled' => false])

@php
    $active = ! $disabled && request()->fullUrlIs($href.'*') && $href !== url('/');

    $icons = [
        'home' => '<path d="M3 10.5 12 3l9 7.5M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/>',
        'clipboard' => '<rect x="8" y="3" width="8" height="4" rx="1"/><path d="M8 5H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 12h6M9 16h4"/>',
        'stack' => '<path d="m12 3 9 5-9 5-9-5 9-5ZM3 13l9 5 9-5M3 17l9 5 9-5"/>',
        'chart' => '<path d="M4 20V6M4 20h16M9 20V11m5 9V7m5 13v-5"/>',
        'users' => '<path d="M16 19v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1M9.5 10a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM21 19v-1a4 4 0 0 0-3-3.87M16.5 3.13A4 4 0 0 1 16.5 11"/>',
        'building' => '<path d="M4 21V5a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v16M15 21V9h3a2 2 0 0 1 2 2v10M4 21h17M8 7h3M8 11h3M8 15h3"/>',
        'link' => '<path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1.5 1.5M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1.5-1.5"/>',
        'cog' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9c.14.5.53.87 1.02 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/>',
        'shield' => '<path d="M12 3 4 6v6c0 5 3.4 8.3 8 9 4.6-.7 8-4 8-9V6l-8-3ZM9 12l2 2 4-4"/>',
        'help' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 0 1 4.9.7c0 1.7-2.5 2.3-2.5 4M12 17h.01"/>',
        'ticket' => '<path d="M4 9V7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4ZM13 5v14"/>',
    ];
    $path = $icons[$icon] ?? null;
@endphp

@if ($disabled)
    <span class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-ink-400" aria-disabled="true" title="Tersedia pada fase berikutnya">
        @if ($path)<svg class="size-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">{!! $path !!}</svg>@endif
        <span>{{ $slot }}</span>
        <span class="ml-auto text-[10px] uppercase tracking-wide text-ink-400">segera</span>
    </span>
@else
    <a href="{{ $href }}" wire:navigate @if($active) aria-current="page" @endif
       @class([
           'group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
           'bg-brand-50 text-brand-800 dark:bg-brand-950/50 dark:text-brand-100' => $active,
           'text-ink-600 hover:bg-ink-100 hover:text-ink-900 dark:text-ink-300 dark:hover:bg-ink-800' => ! $active,
       ])>
        @if ($active)
            <span class="absolute inset-y-1.5 left-0 w-0.5 rounded-full bg-brand-700" aria-hidden="true"></span>
        @endif
        @if ($path)
            <svg class="size-[18px] shrink-0 {{ $active ? 'text-brand-700 dark:text-brand-300' : 'text-ink-400 group-hover:text-ink-600 dark:group-hover:text-ink-200' }}"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">{!! $path !!}</svg>
        @endif
        <span class="truncate">{{ $slot }}</span>
    </a>
@endif

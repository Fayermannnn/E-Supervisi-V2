@props(['href', 'icon' => null, 'disabled' => false])

@php
    $active = ! $disabled && request()->fullUrlIs($href.'*') && $href !== url('/');
    $base = 'flex items-center gap-3 rounded-md px-3 py-2 font-medium transition';
    $state = $disabled
        ? 'cursor-not-allowed text-ink-400'
        : ($active
            ? 'bg-brand-50 text-brand-700 dark:bg-brand-900/40 dark:text-brand-100'
            : 'text-ink-600 hover:bg-ink-100 dark:text-ink-300 dark:hover:bg-ink-800');
@endphp

@if ($disabled)
    <span class="{{ $base }} {{ $state }}" aria-disabled="true" title="Tersedia pada fase berikutnya">
        <span>{{ $slot }}</span>
        <span class="ml-auto text-[10px] uppercase tracking-wide text-ink-400">segera</span>
    </span>
@else
    <a href="{{ $href }}" wire:navigate @if($active) aria-current="page" @endif class="{{ $base }} {{ $state }}">
        {{ $slot }}
    </a>
@endif

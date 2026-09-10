@props(['title' => null, 'subtitle' => null, 'flush' => false])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-[var(--border)] bg-[var(--surface)] shadow-sm']) }}>
    @if ($title || isset($actions))
        <div class="flex items-start justify-between gap-4 border-b border-[var(--border)] bg-[var(--surface-muted)]/40 px-5 py-3.5">
            <div class="min-w-0">
                @if ($title)
                    <h2 class="text-sm font-semibold text-ink-900 dark:text-ink-50">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="mt-0.5 text-xs text-[var(--text-muted)]">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div class="{{ $flush ? '' : 'px-5 py-4' }}">
        {{ $slot }}
    </div>
</div>

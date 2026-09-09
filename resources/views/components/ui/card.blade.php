@props(['title' => null, 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-[var(--border)] bg-[var(--surface)] shadow-sm']) }}>
    @if ($title || isset($actions))
        <div class="flex items-start justify-between gap-4 border-b border-[var(--border)] px-5 py-4">
            <div>
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

    <div class="{{ $attributes->get('flush') ? '' : 'px-5 py-4' }}">
        {{ $slot }}
    </div>
</div>

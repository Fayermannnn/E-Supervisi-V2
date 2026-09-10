@props(['title', 'description' => null, 'eyebrow' => null])

<div {{ $attributes->merge(['class' => 'border-b border-[var(--border)] pb-5']) }}>
    @isset($breadcrumb)
        <div class="mb-2">{{ $breadcrumb }}</div>
    @endisset

    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            @if ($eyebrow)
                <p class="text-xs font-semibold uppercase tracking-[0.08em] text-brand-700 dark:text-brand-400">{{ $eyebrow }}</p>
            @endif
            <h1 class="mt-0.5 text-2xl font-bold tracking-tight text-ink-900 dark:text-ink-50 sm:text-[1.7rem]">{{ $title }}</h1>
            @if ($description)
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-[var(--text-muted)]">{{ $description }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
</div>

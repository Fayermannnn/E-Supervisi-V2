@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div>
        <h1 class="text-xl font-semibold tracking-tight text-ink-900 dark:text-ink-50">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 text-sm text-[var(--text-muted)]">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endisset
</div>

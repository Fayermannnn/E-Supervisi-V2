@props(['title' => 'Belum ada data', 'description' => null])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-dashed border-[var(--border)] px-6 py-12 text-center']) }}>
    <p class="text-sm font-medium text-ink-700 dark:text-ink-200">{{ $title }}</p>
    @if ($description)
        <p class="mx-auto mt-1 max-w-sm text-sm text-[var(--text-muted)]">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-4 flex justify-center">{{ $action }}</div>
    @endisset
</div>

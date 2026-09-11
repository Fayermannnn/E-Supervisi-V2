@props([
    'segments' => [],
    'unit' => 'item',
])

@php
    // Part-to-whole, kategori sedikit & bernama panjang → stacked bar horizontal
    // (docs/dataviz — choosing-a-form). Warna = token status (state, bukan
    // identitas) + selalu ada label di legenda; celah 2px memisahkan segmen
    // sewarna yang bersebelahan.
    $rows = collect($segments)
        ->map(fn ($s) => [
            'label' => (string) ($s['label'] ?? ''),
            'value' => (int) ($s['value'] ?? 0),
            'tone' => (string) ($s['tone'] ?? 'neutral'),
        ])
        ->filter(fn ($s) => $s['value'] > 0)
        ->values();

    $total = $rows->sum('value');

    $toneClass = [
        'draft' => 'bg-status-draft',
        'scheduled' => 'bg-status-scheduled',
        'progress' => 'bg-status-progress',
        'done' => 'bg-status-done',
        'overdue' => 'bg-status-overdue',
        'archived' => 'bg-status-archived',
        'canceled' => 'bg-status-archived',
        'brand' => 'bg-brand-600',
        'neutral' => 'bg-ink-400',
    ];
    $barClass = fn (string $t) => $toneClass[$t] ?? 'bg-ink-400';
@endphp

<div {{ $attributes }}>
    @if ($total > 0)
        <div class="flex h-3 w-full gap-0.5 overflow-hidden rounded-md bg-[var(--surface-sunken)]"
            role="img"
            aria-label="Distribusi {{ $unit }}: {{ $rows->map(fn ($s) => $s['value'].' '.$s['label'])->join(', ') }}">
            @foreach ($rows as $s)
                <div class="h-full {{ $barClass($s['tone']) }}"
                    style="width: {{ max(1.5, round($s['value'] / $total * 100, 2)) }}%"
                    title="{{ $s['label'] }}: {{ $s['value'] }}"></div>
            @endforeach
        </div>

        <ul class="mt-3 grid grid-cols-1 gap-x-5 gap-y-1.5 text-xs sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($rows as $s)
                <li class="flex items-center gap-2">
                    <span class="size-2 shrink-0 rounded-[3px] {{ $barClass($s['tone']) }}" aria-hidden="true"></span>
                    <span class="min-w-0 truncate text-ink-600 dark:text-ink-300">{{ $s['label'] }}</span>
                    <span class="ml-auto shrink-0 font-semibold tabular-nums text-ink-900 dark:text-ink-50">{{ $s['value'] }}</span>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-sm text-[var(--text-muted)]">Belum ada data.</p>
    @endif
</div>

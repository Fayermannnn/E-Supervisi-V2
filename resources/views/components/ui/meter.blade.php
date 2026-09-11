@props([
    'label' => null,
    'value' => 0,
    'max' => 1.0,
    'valueLabel' => null,
    'tone' => 'brand',
    'caption' => null,
])

@php
    // Meter: satu rasio terhadap batas. Isi membawa "tingkat"; track = langkah
    // lebih terang dari ramp yang sama (docs/dataviz — marks-and-anatomy).
    $ceiling = (float) $max ?: 1.0;
    $pct = max(0.0, min(100.0, ((float) $value / $ceiling) * 100));

    $tones = [
        'brand' => ['track' => 'bg-brand-100 dark:bg-brand-950', 'fill' => 'bg-brand-600'],
        'done' => ['track' => 'bg-status-done/15', 'fill' => 'bg-status-done'],
        'overdue' => ['track' => 'bg-status-overdue/15', 'fill' => 'bg-status-overdue'],
        'progress' => ['track' => 'bg-status-progress/15', 'fill' => 'bg-status-progress'],
        'scheduled' => ['track' => 'bg-status-scheduled/15', 'fill' => 'bg-status-scheduled'],
        'neutral' => ['track' => 'bg-ink-200/70 dark:bg-ink-800', 'fill' => 'bg-ink-500'],
    ];
    $c = $tones[$tone] ?? $tones['brand'];
    $display = $valueLabel ?? round($pct).'%';
    // Sisakan sedikit lebar agar nilai kecil non-nol tetap terlihat.
    $renderPct = $pct > 0 && $pct < 1.5 ? 1.5 : $pct;
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if ($label !== null || $valueLabel !== null)
        <div class="mb-1 flex items-baseline justify-between gap-3 text-xs">
            <span class="min-w-0 truncate font-medium text-ink-700 dark:text-ink-200">{{ $label }}</span>
            <span class="shrink-0 font-semibold tabular-nums text-ink-900 dark:text-ink-50">{{ $display }}</span>
        </div>
    @endif

    <div class="h-2 w-full overflow-hidden rounded-full {{ $c['track'] }}"
        role="progressbar" aria-valuenow="{{ round($pct) }}" aria-valuemin="0" aria-valuemax="100"
        @if ($label) aria-label="{{ $label }}" @endif>
        <div class="h-full rounded-full {{ $c['fill'] }}" style="width: {{ $renderPct }}%"></div>
    </div>

    @if ($caption)
        <p class="mt-1 text-[0.7rem] text-[var(--text-muted)]">{{ $caption }}</p>
    @endif
</div>

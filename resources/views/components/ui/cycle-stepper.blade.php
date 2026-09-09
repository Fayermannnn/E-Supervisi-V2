@props(['status'])

@php
    use App\Support\Enums\CycleStatus;

    $steps = [
        ['label' => 'Perencanaan', 'at' => CycleStatus::Scheduled],
        ['label' => 'Observasi', 'at' => CycleStatus::ObservationDone],
        ['label' => 'Analisis', 'at' => CycleStatus::AnalysisDone],
        ['label' => 'Umpan Balik', 'at' => CycleStatus::FeedbackGiven],
        ['label' => 'Tindak Lanjut', 'at' => CycleStatus::FollowUpActive],
        ['label' => 'Pelaporan', 'at' => CycleStatus::Reported],
    ];
    $current = $status->value;
    $canceled = $status === CycleStatus::Canceled;
@endphp

<ol class="flex flex-wrap items-center gap-x-1 gap-y-2 text-xs">
    @foreach ($steps as $i => $step)
        @php
            $done = ! $canceled && $current > $step['at']->value;
            $active = ! $canceled && ($current === $step['at']->value || ($current === 0 && $i === 0));
        @endphp
        <li class="flex items-center gap-1">
            <span class="flex size-5 items-center justify-center rounded-full text-[10px] font-semibold
                {{ $done ? 'bg-status-done text-white' : ($active ? 'bg-brand-600 text-white' : 'bg-ink-200 text-ink-500 dark:bg-ink-700') }}">
                {{ $done ? '✓' : $i + 1 }}
            </span>
            <span class="{{ $active ? 'font-semibold text-ink-900 dark:text-ink-50' : 'text-[var(--text-muted)]' }}">{{ $step['label'] }}</span>
            @if (! $loop->last)
                <span class="mx-1 h-px w-5 bg-ink-200 dark:bg-ink-700"></span>
            @endif
        </li>
    @endforeach
    @if ($canceled)
        <li class="ml-2"><x-ui.status-badge status="canceled" label="Dibatalkan" /></li>
    @endif
</ol>

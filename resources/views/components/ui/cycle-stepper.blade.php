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

@if ($canceled)
    <div class="flex items-center gap-2 text-sm text-[var(--text-muted)]">
        <x-ui.status-badge status="canceled" label="Dibatalkan" />
        <span>Siklus dihentikan.</span>
    </div>
@else
    <ol class="grid grid-cols-3 gap-y-4 sm:grid-cols-6">
        @foreach ($steps as $i => $step)
            @php
                $done = $current > $step['at']->value;
                $active = $current === $step['at']->value || ($current === 0 && $i === 0);
            @endphp
            <li class="relative flex flex-col items-center px-1 text-center">
                @unless ($loop->last)
                    <span aria-hidden="true"
                        class="absolute left-1/2 top-3.5 hidden h-0.5 w-full sm:block {{ $done ? 'bg-status-done' : 'bg-[var(--border-strong)]' }}"></span>
                @endunless
                <span class="relative z-10 flex size-7 items-center justify-center rounded-full text-xs font-bold ring-4 ring-[var(--surface)]
                    {{ $done ? 'bg-status-done text-white' : ($active ? 'bg-brand-700 text-white' : 'bg-[var(--surface-sunken)] text-ink-400 dark:bg-ink-700') }}">
                    {{ $done ? '✓' : $i + 1 }}
                </span>
                <span class="mt-1.5 text-[0.7rem] font-medium leading-tight {{ $active || $done ? 'text-ink-900 dark:text-ink-50' : 'text-[var(--text-muted)]' }}">
                    {{ $step['label'] }}
                </span>
            </li>
        @endforeach
    </ol>
@endif

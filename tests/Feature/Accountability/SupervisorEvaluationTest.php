<?php

declare(strict_types=1);

use App\Domain\Accountability\AccountabilityAggregator;
use App\Domain\Accountability\Actions\SubmitSupervisorEvaluation;
use App\Models\SupervisorEvaluation;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use Illuminate\Auth\Access\AuthorizationException;

function fullAnswers(int $v = 3): array
{
    return ['kejelasan' => $v, 'keadilan' => $v, 'dukungan' => $v, 'umpan_balik' => $v, 'rasa_hormat' => $v];
}

it('lets the teacher submit a 360 evaluation once feedback has been given', function () {
    $c = fase4Cycle(CycleStatus::FeedbackGiven);

    $evaluation = app(SubmitSupervisorEvaluation::class)->handle($c['guru'], $c['cycle'], fullAnswers(4), 'Supervisi membantu.');

    expect($evaluation->supervisor_id)->toBe($c['supervisor']->id)
        ->and($evaluation->jawaban['kejelasan'])->toBe(4)
        ->and(SupervisorEvaluation::where('cycle_id', $c['cycle']->id)->count())->toBe(1);

    // re-submit updates in place
    app(SubmitSupervisorEvaluation::class)->handle($c['guru'], $c['cycle'], fullAnswers(2));
    expect(SupervisorEvaluation::where('cycle_id', $c['cycle']->id)->sole()->jawaban['kejelasan'])->toBe(2);
});

it('refuses a 360 evaluation before feedback and after the cycle is reported', function () {
    $early = fase4Cycle(CycleStatus::AnalysisDone);
    expect(fn () => app(SubmitSupervisorEvaluation::class)->handle($early['guru'], $early['cycle'], fullAnswers()))
        ->toThrow(DomainException::class);

    $late = fase4Cycle(CycleStatus::Reported);
    expect(fn () => app(SubmitSupervisorEvaluation::class)->handle($late['guru'], $late['cycle'], fullAnswers()))
        ->toThrow(DomainException::class);
});

it('forbids anyone other than the cycle teacher from evaluating', function () {
    $c = fase4Cycle(CycleStatus::FeedbackGiven);

    expect(fn () => app(SubmitSupervisorEvaluation::class)->handle($c['supervisor'], $c['cycle'], fullAnswers()))
        ->toThrow(AuthorizationException::class);
});

it('suppresses aggregates below the anonymity threshold and reveals them at/above it', function () {
    $aggregator = app(AccountabilityAggregator::class);

    $supervisor = User::factory()->supervisor()->create();

    // 2 respons < ambang default 3 → belum cukup
    for ($i = 0; $i < 2; $i++) {
        SupervisorEvaluation::create([
            'cycle_id' => fase4Cycle(CycleStatus::FeedbackGiven)['cycle']->id,
            'guru_id' => User::factory()->guru()->create()->id,
            'supervisor_id' => $supervisor->id,
            'dinas_id' => App\Models\Dinas::factory()->create()->id,
            'sekolah_id' => null,
            'jawaban' => fullAnswers(3),
            'submitted_at' => now(),
        ]);
    }
    expect($aggregator->forSupervisor($supervisor)['cukup'])->toBeFalse();

    SupervisorEvaluation::create([
        'cycle_id' => fase4Cycle(CycleStatus::FeedbackGiven)['cycle']->id,
        'guru_id' => User::factory()->guru()->create()->id,
        'supervisor_id' => $supervisor->id,
        'dinas_id' => App\Models\Dinas::factory()->create()->id,
        'sekolah_id' => null,
        'jawaban' => fullAnswers(3),
        'submitted_at' => now(),
    ]);

    $agg = $aggregator->forSupervisor($supervisor);
    expect($agg['cukup'])->toBeTrue()
        ->and($agg['responden'])->toBe(3)
        ->and($agg['dimensi']['kejelasan'])->toBe(3.0)
        ->and($agg['rata_keseluruhan'])->toBe(3.0);
});

it('never triggers a cycle status transition', function () {
    $c = fase4Cycle(CycleStatus::FeedbackGiven);
    app(SubmitSupervisorEvaluation::class)->handle($c['guru'], $c['cycle'], fullAnswers());

    expect($c['cycle']->refresh()->status)->toBe(CycleStatus::FeedbackGiven);
});

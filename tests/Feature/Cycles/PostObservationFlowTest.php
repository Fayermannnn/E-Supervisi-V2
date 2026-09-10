<?php

declare(strict_types=1);

use App\Domain\Analysis\Actions\FinalizeAnalysis;
use App\Domain\Analysis\Actions\PerformAnalysis;
use App\Domain\Analysis\Actions\SaveAnalysisSummary;
use App\Domain\Feedback\Actions\AcknowledgeFeedback;
use App\Domain\Feedback\Actions\PostFeedbackMessage;
use App\Domain\Feedback\Actions\StartFeedbackSession;
use App\Domain\FollowUp\Actions\CreateFollowUpPlan;
use App\Domain\FollowUp\Actions\DetectOverdueFollowUps;
use App\Domain\FollowUp\Actions\SubmitFollowUpEvidence;
use App\Domain\FollowUp\Actions\UpdateFollowUpItem;
use App\Domain\Observation\Actions\FinalizeObservation;
use App\Domain\Observation\Actions\SaveObservation;
use App\Domain\Observation\Actions\StartObservation;
use App\Domain\Planning\Actions\RecordPlanningAgreementConsent;
use App\Domain\Planning\Actions\SavePlanningAgreement;
use App\Domain\Supervision\Actions\CreateCycle;
use App\Models\AnalysisResult;
use App\Models\Dinas;
use App\Models\FollowUpPlan;
use App\Models\Instrument;
use App\Models\Sekolah;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use App\Support\Enums\SupervisorType;
use Illuminate\Support\Facades\Notification;

function observedCycle(): array
{
    $dinas = Dinas::factory()->create();
    $sekolah = Sekolah::factory()->forDinas($dinas)->create();
    $supervisor = User::factory()->atSekolah($sekolah)->supervisor(SupervisorType::KepalaSekolah)->create();
    $guru = User::factory()->atSekolah($sekolah)->guru()->create();
    SupervisorAssignment::create(['supervisor_id' => $supervisor->id, 'guru_id' => $guru->id, 'mulai' => now()->subMonth()->toDateString()]);
    $version = Instrument::factory()->published()->create()->versions()->first();

    $cycle = app(CreateCycle::class)->handle($supervisor, $guru, '2026/2027', 'ganjil', 'Uji');
    app(SavePlanningAgreement::class)->handle($supervisor, $cycle, [
        'fokus_observasi' => 'Fokus', 'instrument_version_id' => $version->id,
        'tipe_observasi' => 'sinkron', 'jadwal_mulai' => now()->addDay()->toDateTimeString(),
    ]);
    app(RecordPlanningAgreementConsent::class)->handle($guru, $cycle);
    app(RecordPlanningAgreementConsent::class)->handle($supervisor, $cycle);

    $obs = app(StartObservation::class)->handle($supervisor, $cycle->refresh());
    $rows = [];
    foreach ($version->schema()->requiredItemKeys() as $key) {
        $rows[] = ['item_key' => $key, 'section_key' => 'inti', 'value' => 3];
    }
    app(SaveObservation::class)->handle($obs, $rows);
    app(FinalizeObservation::class)->handle($supervisor, $obs->refresh());

    return compact('dinas', 'supervisor', 'guru', 'cycle');
}

it('scores an observation deterministically and lets a human finalize the analysis', function () {
    $c = observedCycle();

    $result = app(PerformAnalysis::class)->handle($c['supervisor'], $c['cycle']->refresh());
    expect($result->score_summary['total'])->toBeGreaterThan(0.0)
        ->and($result->findings()->count())->toBeGreaterThan(0);

    // Cannot finalize without a summary
    expect(fn () => app(FinalizeAnalysis::class)->handle($c['supervisor'], $result))
        ->toThrow(DomainException::class);

    app(SaveAnalysisSummary::class)->handle($c['supervisor'], $result, str_repeat('Ringkasan analisis yang memadai. ', 3));
    app(FinalizeAnalysis::class)->handle($c['supervisor'], $result->refresh());

    expect($c['cycle']->refresh()->status)->toBe(CycleStatus::AnalysisDone)
        ->and($result->refresh()->reviewed_by)->toBe($c['supervisor']->id);
});

it('will not finalize an analysis whose summary is a raw AI draft', function () {
    $c = observedCycle();
    $result = app(PerformAnalysis::class)->handle($c['supervisor'], $c['cycle']->refresh());

    // Simulate a raw AI draft summary (sumber ai_draft) not reviewed
    $result->forceFill(['ringkasan' => str_repeat('teks ai mentah ', 5), 'sumber' => AnalysisResult::SUMBER_AI_DRAFT])->save();

    expect(fn () => app(FinalizeAnalysis::class)->handle($c['supervisor'], $result->refresh()))
        ->toThrow(DomainException::class, 'draf AI mentah');
});

it('advances to feedback only after the teacher acknowledges', function () {
    $c = observedCycle();
    $result = app(PerformAnalysis::class)->handle($c['supervisor'], $c['cycle']->refresh());
    app(SaveAnalysisSummary::class)->handle($c['supervisor'], $result, str_repeat('Ringkasan. ', 5));
    app(FinalizeAnalysis::class)->handle($c['supervisor'], $result->refresh());

    $session = app(StartFeedbackSession::class)->handle($c['cycle']->refresh());
    app(PostFeedbackMessage::class)->handle($c['supervisor'], $session, 'observasi', 'Mari bahas hasilnya.');

    expect($c['cycle']->refresh()->status)->toBe(CycleStatus::AnalysisDone);

    app(AcknowledgeFeedback::class)->handle($c['guru'], $session);
    expect($c['cycle']->refresh()->status)->toBe(CycleStatus::FeedbackGiven);
});

it('creates an RTL, escalates when overdue, and recovers when evidence lands', function () {
    Notification::fake();
    $c = observedCycle();
    $result = app(PerformAnalysis::class)->handle($c['supervisor'], $c['cycle']->refresh());
    app(SaveAnalysisSummary::class)->handle($c['supervisor'], $result, str_repeat('Ringkasan. ', 5));
    app(FinalizeAnalysis::class)->handle($c['supervisor'], $result->refresh());
    $session = app(StartFeedbackSession::class)->handle($c['cycle']->refresh());
    app(PostFeedbackMessage::class)->handle($c['supervisor'], $session, 'kesepakatan', 'Sepakat.');
    app(AcknowledgeFeedback::class)->handle($c['guru'], $session);

    $plan = app(CreateFollowUpPlan::class)->handle(
        $c['supervisor'], $c['cycle']->refresh(),
        'Tingkatkan partisipasi.', now()->subDay()->toDateString(),
        [['deskripsi' => 'Butir A', 'indikator_keberhasilan' => 'Indikator A']],
    );

    expect($c['cycle']->refresh()->status)->toBe(CycleStatus::FollowUpActive);

    // Daily job flags it overdue and escalates
    app(DetectOverdueFollowUps::class)->handle();
    expect($c['cycle']->refresh()->status)->toBe(CycleStatus::FollowUpOverdue)
        ->and($plan->refresh()->status)->toBe(FollowUpPlan::STATUS_TERLAMBAT);
    Notification::assertSentTo($c['supervisor'], App\Domain\FollowUp\Notifications\FollowUpEscalationNotification::class);

    // Guru submits evidence + completes the item -> plan closes -> cycle recovers
    $item = $plan->items()->first();
    app(SubmitFollowUpEvidence::class)->handle($c['guru'], $item, ['tipe' => 'catatan', 'deskripsi' => 'Sudah dikerjakan']);
    app(UpdateFollowUpItem::class)->handle($c['guru'], $item->refresh(), 'selesai');

    expect($plan->refresh()->status)->toBe(FollowUpPlan::STATUS_SELESAI);
    app(DetectOverdueFollowUps::class)->handle();
    expect($c['cycle']->refresh()->status)->toBe(CycleStatus::FollowUpActive);
});

it('compiles a cycle report and moves the cycle to Reported', function () {
    $c = observedCycle();
    $result = app(PerformAnalysis::class)->handle($c['supervisor'], $c['cycle']->refresh());
    app(SaveAnalysisSummary::class)->handle($c['supervisor'], $result, str_repeat('Ringkasan. ', 5));
    app(FinalizeAnalysis::class)->handle($c['supervisor'], $result->refresh());
    $session = app(StartFeedbackSession::class)->handle($c['cycle']->refresh());
    app(PostFeedbackMessage::class)->handle($c['supervisor'], $session, 'kesepakatan', 'Sepakat.');
    app(AcknowledgeFeedback::class)->handle($c['guru'], $session);
    $plan = app(CreateFollowUpPlan::class)->handle($c['supervisor'], $c['cycle']->refresh(), 'Tujuan.', now()->addWeek()->toDateString(),
        [['deskripsi' => 'A', 'indikator_keberhasilan' => 'I']]);
    app(UpdateFollowUpItem::class)->handle($c['supervisor'], $plan->items()->first(), 'selesai');

    $report = app(App\Domain\Reporting\Actions\CompileCycleReport::class)->handle($c['supervisor'], $c['cycle']->refresh());

    expect($report->status)->toBe('siap')
        ->and($c['cycle']->refresh()->status)->toBe(CycleStatus::Reported)
        ->and($report->snapshot->data['analisis']['ringkasan'])->toContain('Ringkasan');
});

<?php

declare(strict_types=1);

use App\Domain\Evaluation\Actions\AssignExpertToPanel;
use App\Domain\Evaluation\Actions\CloseEvaluationPanel;
use App\Domain\Evaluation\Actions\CreateEvaluationPanel;
use App\Domain\Evaluation\Actions\SubmitExpertReview;
use App\Domain\Evaluation\ExpertJudgmentInstrument;
use App\Domain\Evaluation\UsabilityQuestionnaire;
use App\Models\EvaluationPanel;
use App\Models\PanelExpert;
use App\Models\User;
use App\Support\Enums\Role;
use Illuminate\Auth\Access\AuthorizationException;

function completeJawaban(string $relevansi = 'esensial', int $kualitas = 4): array
{
    $jawaban = ['relevansi' => [], 'kualitas' => [], 'sus' => []];
    foreach (array_keys(ExpertJudgmentInstrument::aspects()) as $aspek) {
        $jawaban['relevansi'][$aspek] = $relevansi;
        $jawaban['kualitas'][$aspek] = $kualitas;
    }
    foreach (array_keys(UsabilityQuestionnaire::items()) as $key) {
        $jawaban['sus'][$key] = 4;
    }

    return $jawaban;
}

function researcher(): User
{
    return User::factory()->adminSistem()->create();
}

it('runs a panel from creation to a computed stats snapshot', function () {
    $peneliti = researcher();

    $panel = app(CreateEvaluationPanel::class)->handle($peneliti, [
        'judul' => 'Validasi Artefak E-Supervisi', 'artefak_versi' => 'MVP Fase 1-5',
    ]);
    expect($panel->status)->toBe(EvaluationPanel::STATUS_DRAFT);

    $experts = [];
    foreach ([['a@e.test', 'manajemen_pendidikan'], ['b@e.test', 'manajemen_pendidikan'], ['c@e.test', 'sistem_informasi']] as [$email, $rumpun]) {
        $user = User::factory()->create(['email' => $email]);
        app(AssignExpertToPanel::class)->handle($peneliti, $panel, $user, $rumpun);
        expect($user->refresh()->hasRole(Role::Ahli))->toBeTrue();
        $experts[] = $user;
    }

    expect($panel->refresh()->status)->toBe(EvaluationPanel::STATUS_BERJALAN);

    foreach ($experts as $i => $expert) {
        $pe = PanelExpert::where('evaluation_panel_id', $panel->id)->where('user_id', $expert->id)->sole();
        app(SubmitExpertReview::class)->handle($expert, $pe, completeJawaban('esensial', $i === 2 ? 5 : 4));
    }

    $closed = app(CloseEvaluationPanel::class)->handle($peneliti, $panel->refresh());

    expect($closed->status)->toBe(EvaluationPanel::STATUS_SELESAI)
        ->and($closed->stats['n_ahli'])->toBe(3)
        ->and((float) $closed->stats['cvi'])->toBe(1.0)
        ->and((float) $closed->stats['aiken_v_rata'])->toBeGreaterThan(0.7)
        ->and($closed->stats['per_rumpun'])->toHaveKeys(['manajemen_pendidikan', 'sistem_informasi'])
        ->and($closed->closed_at)->not->toBeNull();
});

it('rejects an incomplete review', function () {
    $peneliti = researcher();
    $panel = app(CreateEvaluationPanel::class)->handle($peneliti, ['judul' => 'Panel X', 'artefak_versi' => 'v1']);
    $expert = User::factory()->create();
    $pe = app(AssignExpertToPanel::class)->handle($peneliti, $panel, $expert, 'sistem_informasi');

    $partial = completeJawaban();
    unset($partial['sus']['s5']);

    expect(fn () => app(SubmitExpertReview::class)->handle($expert, $pe, $partial))
        ->toThrow(DomainException::class);
});

it('forbids a non-assigned expert and a non-researcher', function () {
    $peneliti = researcher();
    $panel = app(CreateEvaluationPanel::class)->handle($peneliti, ['judul' => 'Panel Y', 'artefak_versi' => 'v1']);
    $expert = User::factory()->create();
    $pe = app(AssignExpertToPanel::class)->handle($peneliti, $panel, $expert, 'sistem_informasi');

    $intruder = User::factory()->create();
    $intruder->assignRole(Role::Ahli);
    expect(fn () => app(SubmitExpertReview::class)->handle($intruder, $pe, completeJawaban()))
        ->toThrow(AuthorizationException::class);

    $guru = User::factory()->guru()->create();
    expect(fn () => app(CreateEvaluationPanel::class)->handle($guru, ['judul' => 'Z', 'artefak_versi' => 'v1']))
        ->toThrow(AuthorizationException::class);
});

it('will not close a panel with no submitted reviews', function () {
    $peneliti = researcher();
    $panel = app(CreateEvaluationPanel::class)->handle($peneliti, ['judul' => 'Panel W', 'artefak_versi' => 'v1']);
    app(AssignExpertToPanel::class)->handle($peneliti, $panel, User::factory()->create(), 'lainnya');

    expect(fn () => app(CloseEvaluationPanel::class)->handle($peneliti, $panel->refresh()))
        ->toThrow(DomainException::class);
});

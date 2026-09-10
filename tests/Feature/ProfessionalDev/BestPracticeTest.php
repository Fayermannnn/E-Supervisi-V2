<?php

declare(strict_types=1);

use App\Domain\ProfessionalDev\Actions\CurateBestPractice;
use App\Domain\ProfessionalDev\Actions\NominateBestPractice;
use App\Domain\ProfessionalDev\Actions\RespondBestPracticeConsent;
use App\Models\AnalysisResult;
use App\Models\BestPractice;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use App\Support\Enums\Role;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;

function bumpScore(string $cycleId, float $total): void
{
    $r = AnalysisResult::where('cycle_id', $cycleId)->sole();
    $summary = $r->score_summary;
    $summary['total'] = $total;
    $summary['band'] = 'Sangat Baik';
    $r->forceFill(['score_summary' => $summary])->save();
}

function nominationPayload(): array
{
    return [
        'judul' => 'Diskusi kelompok terstruktur',
        'ringkasan' => 'Guru menstrukturkan diskusi kelompok dengan peran bergilir.',
        'praktik' => str_repeat('Langkah praktik yang dapat ditiru guru lain. ', 3),
        'tags' => ['diskusi kelompok'],
        'anonim' => false,
    ];
}

it('runs the full nominate → consent → curate → publish flow', function () {
    Notification::fake();
    $c = fase4Cycle(CycleStatus::Reported);
    bumpScore($c['cycle']->id, 0.9);

    $admin = User::factory()->create();
    $admin->assignRole(Role::AdminDinas, $c['dinas']);

    $bp = app(NominateBestPractice::class)->handle($c['supervisor'], $c['cycle']->refresh(), nominationPayload());
    expect($bp->status)->toBe(BestPractice::STATUS_MENUNGGU_CONSENT);

    app(RespondBestPracticeConsent::class)->handle($c['guru'], $bp, true);
    expect($bp->refresh()->status)->toBe(BestPractice::STATUS_MENUNGGU_KURASI)
        ->and($bp->consent_by)->toBe($c['guru']->id);

    app(CurateBestPractice::class)->handle($admin, $bp->refresh(), true, 'Bagus, layak dibagikan.');
    expect($bp->refresh()->status)->toBe(BestPractice::STATUS_TERBIT)
        ->and($bp->terbit_at)->not->toBeNull();
});

it('blocks nomination when the cycle score is below the policy threshold', function () {
    $c = fase4Cycle(CycleStatus::Reported);
    bumpScore($c['cycle']->id, 0.4);

    expect(fn () => app(NominateBestPractice::class)->handle($c['supervisor'], $c['cycle']->refresh(), nominationPayload()))
        ->toThrow(DomainException::class);
});

it('blocks nomination before the cycle is reported', function () {
    $c = fase4Cycle(CycleStatus::FollowUpActive);
    bumpScore($c['cycle']->id, 0.95);

    expect(fn () => app(NominateBestPractice::class)->handle($c['supervisor'], $c['cycle']->refresh(), nominationPayload()))
        ->toThrow(DomainException::class);
});

it('does not publish without teacher consent', function () {
    $c = fase4Cycle(CycleStatus::Reported);
    bumpScore($c['cycle']->id, 0.9);
    $admin = User::factory()->create();
    $admin->assignRole(Role::AdminDinas, $c['dinas']);

    $bp = app(NominateBestPractice::class)->handle($c['supervisor'], $c['cycle']->refresh(), nominationPayload());

    // guru menolak
    app(RespondBestPracticeConsent::class)->handle($c['guru'], $bp, false);
    expect($bp->refresh()->status)->toBe(BestPractice::STATUS_DITOLAK);

    expect(fn () => app(CurateBestPractice::class)->handle($admin, $bp->refresh(), true))
        ->toThrow(DomainException::class);
});

it('forbids an admin dinas from another dinas curating the entry', function () {
    $c = fase4Cycle(CycleStatus::Reported);
    bumpScore($c['cycle']->id, 0.9);
    $bp = app(NominateBestPractice::class)->handle($c['supervisor'], $c['cycle']->refresh(), nominationPayload());
    app(RespondBestPracticeConsent::class)->handle($c['guru'], $bp, true);

    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole(Role::AdminDinas, App\Models\Dinas::factory()->create());

    expect(fn () => app(CurateBestPractice::class)->handle($otherAdmin, $bp->refresh(), true))
        ->toThrow(AuthorizationException::class);
});

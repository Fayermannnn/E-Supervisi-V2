<?php

declare(strict_types=1);

use App\Domain\Accountability\Actions\AddCalibrationParticipant;
use App\Domain\Accountability\Actions\CloseCalibrationSession;
use App\Domain\Accountability\Actions\CreateCalibrationSession;
use App\Domain\Accountability\Actions\SubmitCalibrationScores;
use App\Models\CalibrationSession;
use App\Models\Instrument;
use App\Models\User;
use App\Support\Enums\Role;
use App\Support\Enums\SupervisorType;
use Illuminate\Auth\Access\AuthorizationException;

function calibrationContext(): array
{
    $dinas = App\Models\Dinas::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole(Role::AdminDinas, $dinas);
    $version = Instrument::factory()->published()->create()->versions()->first();
    $raters = User::factory()->count(3)->supervisor(SupervisorType::Pengawas)->create();

    return compact('dinas', 'admin', 'version', 'raters');
}

it('runs a calibration session end to end and stores a deterministic stats snapshot', function () {
    $ctx = calibrationContext();

    $session = app(CreateCalibrationSession::class)->handle($ctx['admin'], [
        'instrument_version_id' => $ctx['version']->id,
        'judul' => 'Kalibrasi Format B',
        'deskripsi' => null,
    ]);
    expect($session->dinas_id)->toBe($ctx['dinas']->id);

    $itemKeys = $ctx['version']->schema()->requiredItemKeys();

    foreach ($ctx['raters'] as $i => $rater) {
        app(AddCalibrationParticipant::class)->handle($ctx['admin'], $session, $rater);
        $scores = [];
        foreach ($itemKeys as $key) {
            $scores[$key] = 3 + ($i === 2 ? 1 : 0); // dua rater identik, satu berbeda
        }
        app(SubmitCalibrationScores::class)->handle($rater, $session->refresh(), $scores);
    }

    expect($session->refresh()->status)->toBe(CalibrationSession::STATUS_BERJALAN);

    $closed = app(CloseCalibrationSession::class)->handle($ctx['admin'], $session->refresh());

    expect($closed->status)->toBe(CalibrationSession::STATUS_SELESAI)
        ->and($closed->stats['n_rater'])->toBe(3)
        ->and($closed->stats['persen_kesepakatan'])->toBe(round(2 / 3, 3))
        ->and($closed->closed_at)->not->toBeNull();
});

it('refuses to close a session with fewer than two submitted raters', function () {
    $ctx = calibrationContext();
    $session = app(CreateCalibrationSession::class)->handle($ctx['admin'], [
        'instrument_version_id' => $ctx['version']->id, 'judul' => 'X', 'deskripsi' => null,
    ]);
    $rater = $ctx['raters']->first();
    app(AddCalibrationParticipant::class)->handle($ctx['admin'], $session, $rater);
    app(SubmitCalibrationScores::class)->handle($rater, $session->refresh(), [
        $ctx['version']->schema()->requiredItemKeys()[0] => 3,
    ]);

    expect(fn () => app(CloseCalibrationSession::class)->handle($ctx['admin'], $session->refresh()))
        ->toThrow(DomainException::class);
});

it('forbids a non-participant from submitting scores', function () {
    $ctx = calibrationContext();
    $session = app(CreateCalibrationSession::class)->handle($ctx['admin'], [
        'instrument_version_id' => $ctx['version']->id, 'judul' => 'X', 'deskripsi' => null,
    ]);
    app(AddCalibrationParticipant::class)->handle($ctx['admin'], $session, $ctx['raters']->first());
    $outsider = $ctx['raters']->last();

    expect(fn () => app(SubmitCalibrationScores::class)->handle($outsider, $session->refresh(), ['x' => 3]))
        ->toThrow(AuthorizationException::class);
});

it('forbids a supervisor from creating a calibration session', function () {
    $ctx = calibrationContext();

    expect(fn () => app(CreateCalibrationSession::class)->handle($ctx['raters']->first(), [
        'instrument_version_id' => $ctx['version']->id, 'judul' => 'X', 'deskripsi' => null,
    ]))->toThrow(AuthorizationException::class);
});

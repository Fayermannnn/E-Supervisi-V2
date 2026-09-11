<?php

declare(strict_types=1);

use App\Models\Dinas;
use App\Models\Sekolah;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use App\Support\Enums\Role;

use function Pest\Laravel\actingAs;

it('renders the Fase 4 list screens for the right roles', function (Role $role, string $route) {
    $dinas = Dinas::factory()->create();
    $sekolah = Sekolah::factory()->forDinas($dinas)->create();
    $user = User::factory()->atSekolah($sekolah)->create();
    $user->assignRole($role, $role === Role::AdminDinas ? $dinas : null);

    actingAs($user)->get(route($route))->assertOk();
})->with([
    'supervisor → program tahunan' => [Role::Supervisor, 'programs.index'],
    'supervisor → katalog PKB' => [Role::Supervisor, 'pkb.catalog'],
    'guru → perpustakaan praktik baik' => [Role::Guru, 'best-practices.index'],
    'admin dinas → katalog PKB' => [Role::AdminDinas, 'pkb.catalog'],
    'admin dinas → akuntabilitas' => [Role::AdminDinas, 'accountability.index'],
    'supervisor → akuntabilitas' => [Role::Supervisor, 'accountability.index'],
    'admin dinas → kalibrasi' => [Role::AdminDinas, 'calibration.index'],
]);

it('renders the per-cycle PKB and 360 screens', function () {
    $c = fase4Cycle(CycleStatus::FeedbackGiven);

    actingAs($c['supervisor'])->get(route('cycles.pkb', $c['cycle']))->assertOk();
    actingAs($c['guru'])->get(route('cycles.evaluate', $c['cycle']))->assertOk()->assertSee('360');
});

it('denies the accountability dashboard to a guru', function () {
    $guru = User::factory()->guru()->create();
    actingAs($guru)->get(route('accountability.index'))->assertForbidden();
});

it('renders the accountability dashboard meters once the anonymity threshold is met', function () {
    $supervisor = User::factory()->supervisor()->create();
    $dinas = Dinas::factory()->create();

    for ($i = 0; $i < 3; $i++) {
        App\Models\SupervisorEvaluation::create([
            'cycle_id' => fase4Cycle(CycleStatus::FeedbackGiven)['cycle']->id,
            'guru_id' => User::factory()->guru()->create()->id,
            'supervisor_id' => $supervisor->id,
            'dinas_id' => $dinas->id,
            'sekolah_id' => null,
            'jawaban' => ['kejelasan' => 3, 'keadilan' => 3, 'dukungan' => 3, 'umpan_balik' => 3, 'rasa_hormat' => 3],
            'submitted_at' => now(),
        ]);
    }

    actingAs($supervisor)->get(route('accountability.index'))
        ->assertOk()
        ->assertSee('Rata-rata keseluruhan')
        ->assertDontSee('Data belum cukup');
});

it('renders the program editor and a closed calibration session', function () {
    $c = fase4Pair();
    $program = app(App\Domain\Program\Actions\SaveAnnualProgram::class)->handle($c['supervisor'], null, [
        'judul' => 'Program Uji', 'tahun_ajaran' => '2026/2027', 'semester' => 'ganjil', 'catatan' => null,
    ]);
    actingAs($c['supervisor'])->get(route('programs.show', $program))->assertOk()->assertSee('Program Uji');

    $dinas = Dinas::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole(Role::AdminDinas, $dinas);
    $version = App\Models\Instrument::factory()->published()->create()->versions()->first();
    $session = app(App\Domain\Accountability\Actions\CreateCalibrationSession::class)->handle($admin, [
        'instrument_version_id' => $version->id, 'judul' => 'Sesi Uji', 'deskripsi' => null,
    ]);
    actingAs($admin)->get(route('calibration.show', $session))->assertOk()->assertSee('Sesi Uji');
});

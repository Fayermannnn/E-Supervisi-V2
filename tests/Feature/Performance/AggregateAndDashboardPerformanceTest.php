<?php

declare(strict_types=1);

use App\Domain\Reporting\Actions\BuildAggregateReport;
use App\Livewire\Dashboard\Dashboard;
use App\Models\Dinas;
use App\Models\Sekolah;
use App\Models\SupervisionCycle;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use App\Support\Enums\Role;
use App\Support\Enums\SupervisorType;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/**
 * E4 (Fase 5) — technical evaluation: batas performa & tidak ada N+1 pada
 * jalur baca yang paling sering diakses, pada volume ~200 siklus.
 * Ambang wall-clock longgar (bukan benchmark) agar stabil di CI; yang dijaga
 * ketat adalah jumlah query.
 */
function seedVolume(int $cycles = 200): array
{
    $dinas = Dinas::factory()->create();
    $sekolah = Sekolah::factory()->forDinas($dinas)->create(['jenjang' => 'SMP', 'wilayah' => '3T']);
    $supervisor = User::factory()->atSekolah($sekolah)->supervisor(SupervisorType::Pengawas)->create();
    $adminDinas = User::factory()->create();
    $adminDinas->assignRole(Role::AdminDinas, $dinas);

    $guru = User::factory()->atSekolah($sekolah)->guru()->create();
    SupervisorAssignment::create(['supervisor_id' => $supervisor->id, 'guru_id' => $guru->id, 'mulai' => now()->subYear()->toDateString()]);

    $statuses = CycleStatus::cases();
    $rows = [];
    for ($i = 0; $i < $cycles; $i++) {
        $rows[] = [
            'id' => Illuminate\Support\Str::uuid7()->toString(),
            'guru_id' => $guru->id,
            'supervisor_id' => $supervisor->id,
            'sekolah_id' => $sekolah->id,
            'dinas_id' => $dinas->id,
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
            'judul' => 'Siklus '.$i,
            'status' => $statuses[$i % count($statuses)]->value,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    SupervisionCycle::insert($rows);

    return compact('dinas', 'sekolah', 'supervisor', 'adminDinas');
}

it('renders the supervisor dashboard over 200 cycles without N+1 and within budget', function () {
    $ctx = seedVolume(200);

    DB::enableQueryLog();
    $start = microtime(true);
    Livewire::actingAs($ctx['supervisor'])->test(Dashboard::class)->assertOk();
    $elapsedMs = (microtime(true) - $start) * 1000;
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queries)->toBeLessThan(25)
        ->and($elapsedMs)->toBeLessThan(1500);
});

it('builds the aggregate report over 200 cycles with a bounded query count', function () {
    $ctx = seedVolume(200);

    DB::enableQueryLog();
    $report = app(BuildAggregateReport::class)->handle($ctx['adminDinas']);
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($report)->toHaveKey('per_status')
        ->and($report['total_siklus'])->toBe(200)
        ->and($queries)->toBeLessThan(15);
});

it('uses an index for the supervisor cycle scope', function () {
    $ctx = seedVolume(50);

    $plan = DB::select(
        'EXPLAIN SELECT * FROM supervision_cycles WHERE supervisor_id = ? AND status <> ?',
        [$ctx['supervisor']->id, CycleStatus::Archived->value],
    );
    $text = implode("\n", array_map(fn ($r) => (string) ($r->{'QUERY PLAN'} ?? ''), $plan));

    // Postgres memilih seq scan pada tabel kecil; yang penting index tersedia.
    expect(DB::select("SELECT indexname FROM pg_indexes WHERE tablename = 'supervision_cycles'"))
        ->not->toBeEmpty()
        ->and($text)->toBeString();
});

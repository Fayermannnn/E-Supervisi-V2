<?php

declare(strict_types=1);

use App\Domain\Reporting\Actions\CompileAggregateReport;
use App\Domain\Reporting\Actions\RequestReportExport;
use App\Models\Report;
use App\Models\ReportExport;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use App\Support\Enums\Role;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(fn () => Storage::fake('local'));

function cycleReport(): array
{
    $c = fase4Cycle(CycleStatus::Reported);
    $report = Report::where('scope', 'cycle')->where('scope_id', $c['cycle']->id)->sole();

    return [...$c, 'report' => $report];
}

it('generates a PDF export for a cycle report (queue sync) and stores it privately', function () {
    $c = cycleReport();

    $export = app(RequestReportExport::class)->handle($c['supervisor'], $c['report'], 'pdf');
    $export->refresh();

    expect($export->status)->toBe(ReportExport::STATUS_SIAP)
        ->and($export->path)->toStartWith('reports/')
        ->and($export->ukuran)->toBeGreaterThan(0);

    Storage::disk('local')->assertExists($export->path);
    expect(substr(Storage::disk('local')->get($export->path), 0, 5))->toBe('%PDF-');
});

it('refuses non-PDF formats and unknown formats for a cycle report', function () {
    $c = cycleReport();

    expect(fn () => app(RequestReportExport::class)->handle($c['supervisor'], $c['report'], 'xlsx'))
        ->toThrow(DomainException::class);
    expect(fn () => app(RequestReportExport::class)->handle($c['supervisor'], $c['report'], 'docx'))
        ->toThrow(DomainException::class);
});

it('denies export to a guru and to another supervisor', function () {
    $c = cycleReport();

    expect(fn () => app(RequestReportExport::class)->handle($c['guru'], $c['report'], 'pdf'))
        ->toThrow(AuthorizationException::class);

    $other = User::factory()->supervisor()->create();
    expect(fn () => app(RequestReportExport::class)->handle($other, $c['report'], 'pdf'))
        ->toThrow(AuthorizationException::class);
});

it('exports the aggregate report as PDF, XLSX, and CSV for the dinas admin', function () {
    $c = fase4Cycle(CycleStatus::Reported);
    $admin = User::factory()->create();
    $admin->assignRole(Role::AdminDinas, $c['dinas']);

    $report = app(CompileAggregateReport::class)->handle($admin, []);
    expect($report->scope)->toBe(Report::SCOPE_DINAS);

    foreach (['pdf', 'xlsx', 'csv'] as $format) {
        $export = app(RequestReportExport::class)->handle($admin, $report, $format)->refresh();
        expect($export->status)->toBe(ReportExport::STATUS_SIAP);
        Storage::disk('local')->assertExists($export->path);
    }

    $xlsx = ReportExport::where('format', 'xlsx')->sole();
    expect(substr(Storage::disk('local')->get($xlsx->path), 0, 2))->toBe('PK'); // zip magic
});

it('lets involved parties download a ready export but blocks outsiders', function () {
    $c = cycleReport();
    $export = app(RequestReportExport::class)->handle($c['supervisor'], $c['report'], 'pdf')->refresh();

    // Supervisor & guru siklus dapat mengunduh (data siklus mereka sendiri).
    actingAs($c['supervisor'])->get(route('reports.exports.download', $export))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
    actingAs($c['guru'])->get(route('reports.exports.download', $export))->assertOk();

    // Guru dari siklus lain tidak.
    actingAs(User::factory()->guru()->create())
        ->get(route('reports.exports.download', $export))->assertForbidden();
});

it('exposes the cycle export endpoint over the API', function () {
    $c = cycleReport();
    Laravel\Sanctum\Sanctum::actingAs($c['supervisor']);

    $this->postJson("/api/v1/cycles/{$c['cycle']->id}/reports/export", ['format' => 'pdf'])
        ->assertStatus(202)
        ->assertJsonStructure(['data' => ['id', 'status', 'download_url']]);
});

<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Supervision\StateMachine\CycleStateMachine;
use App\Models\FollowUpPlan;
use App\Models\Report;
use App\Models\ReportSnapshot;
use App\Models\SupervisionCycle;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use App\Support\Enums\Permission;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Menyusun laporan per siklus (M6). Materialisasi snapshot data lalu memicu
 * transisi ke DILAPORKAN. Menolak bila RTL masih terbuka kecuali supervisor
 * memberi catatan override.
 */
class CompileCycleReport
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly CycleStateMachine $stateMachine,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function handle(User $actor, SupervisionCycle $cycle, ?string $overrideNote = null): Report
    {
        if (! $actor->can(Permission::CompileCycleReport->value) || $cycle->supervisor_id !== $actor->getKey()) {
            throw new AuthorizationException('Anda tidak berwenang menyusun laporan siklus ini.');
        }

        if (! in_array($cycle->status, [CycleStatus::FollowUpActive, CycleStatus::FollowUpOverdue, CycleStatus::Reported], true)) {
            throw new DomainException('Laporan disusun setelah tahap tindak lanjut berjalan.');
        }

        $openPlans = FollowUpPlan::query()->where('cycle_id', $cycle->getKey())->open()->count();
        if ($openPlans > 0 && ($overrideNote === null || trim($overrideNote) === '')) {
            throw new DomainException("Masih ada {$openPlans} RTL terbuka. Beri catatan bila tetap ingin melaporkan.");
        }

        $data = $this->buildSnapshot($cycle, $overrideNote);

        return DB::transaction(function () use ($actor, $cycle, $data): Report {
            $report = Report::updateOrCreate(
                ['scope' => Report::SCOPE_CYCLE, 'scope_id' => $cycle->getKey()],
                ['tipe' => 'ringkasan_siklus', 'dibuat_oleh' => $actor->getKey(), 'format' => 'pdf', 'status' => 'siap'],
            );

            ReportSnapshot::where('report_id', $report->id)->delete();
            ReportSnapshot::create([
                'report_id' => $report->id,
                'data' => $data,
                'generated_at' => now(),
            ]);

            $this->audit->log('report.cycle_compiled', $cycle, actor: $actor);

            if ($cycle->status !== CycleStatus::Reported) {
                $this->stateMachine->transition($cycle, CycleStatus::Reported, $actor);
            }

            return $report->refresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSnapshot(SupervisionCycle $cycle, ?string $overrideNote): array
    {
        $cycle->load([
            'guru', 'supervisor', 'sekolah',
            'planningAgreement.instrumentVersion.instrument',
            'transitions.actor',
        ]);

        $analysis = \App\Models\AnalysisResult::with('findings')->where('cycle_id', $cycle->id)->first();
        $feedback = \App\Models\FeedbackSession::with('agreements')->where('cycle_id', $cycle->id)->first();
        $plans = FollowUpPlan::with('items')->where('cycle_id', $cycle->id)->get();

        return [
            'meta' => [
                'judul' => $cycle->judul,
                'guru' => $cycle->guru?->name,
                'supervisor' => $cycle->supervisor?->name,
                'sekolah' => $cycle->sekolah?->nama,
                'tahun_ajaran' => $cycle->tahun_ajaran,
                'semester' => $cycle->semester,
                'disusun_pada' => now()->toIso8601String(),
                'catatan_override' => $overrideNote,
            ],
            'perencanaan' => $cycle->planningAgreement === null ? null : [
                'fokus' => $cycle->planningAgreement->fokus_observasi,
                'instrumen' => $cycle->planningAgreement->instrumentVersion?->instrument?->nama,
                'jadwal' => $cycle->planningAgreement->jadwal_mulai->toIso8601String(),
            ],
            'analisis' => $analysis === null ? null : [
                'skor' => $analysis->score_summary,
                'ringkasan' => $analysis->ringkasan,
                'sumber' => $analysis->sumber,
                'temuan' => $analysis->findings->map(fn ($f) => [
                    'kategori' => $f->kategori, 'deskripsi' => $f->deskripsi,
                ]),
            ],
            'umpan_balik' => $feedback === null ? null : [
                'dikonfirmasi_guru' => $feedback->isConfirmed(),
                'kesepakatan' => $feedback->agreements->pluck('poin_kesepakatan'),
            ],
            'tindak_lanjut' => $plans->map(fn (FollowUpPlan $p): array => [
                'tujuan' => $p->tujuan,
                'tenggat' => $p->tenggat->toDateString(),
                'status' => $p->status,
                'butir' => $p->items->map(fn (\App\Models\FollowUpItem $i): array => [
                    'deskripsi' => $i->deskripsi, 'status' => $i->status,
                ])->all(),
            ])->all(),
            'riwayat_status' => $cycle->transitions->map(fn (\App\Models\CycleStatusTransition $t): array => [
                'dari' => $t->from_status->label(),
                'ke' => $t->to_status->label(),
                'oleh' => $t->actor !== null ? $t->actor->name : 'Sistem',
                'pada' => $t->created_at->toIso8601String(),
            ])->all(),
        ];
    }
}

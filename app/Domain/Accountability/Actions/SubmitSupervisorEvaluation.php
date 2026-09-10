<?php

declare(strict_types=1);

namespace App\Domain\Accountability\Actions;

use App\Domain\Accountability\SupervisionProcessSurvey;
use App\Domain\Audit\AuditLogger;
use App\Models\SupervisionCycle;
use App\Models\SupervisorEvaluation;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use App\Support\Enums\Permission;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Guru mengirim / memperbarui penilaian 360° atas proses supervisi (M11).
 * Terbuka sejak FEEDBACK_GIVEN, dapat disunting hingga siklus REPORTED.
 * Tidak memicu transisi status siklus (RULE 4 / Spec §11).
 */
class SubmitSupervisorEvaluation
{
    private const OPEN_STATUSES = [
        CycleStatus::FeedbackGiven,
        CycleStatus::FollowUpActive,
        CycleStatus::FollowUpOverdue,
    ];

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $answers
     *
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function handle(User $guru, SupervisionCycle $cycle, array $answers, ?string $komentar = null): SupervisorEvaluation
    {
        if ($cycle->guru_id !== $guru->getKey() || ! $guru->can(Permission::SubmitSupervisorEvaluation->value)) {
            throw new AuthorizationException('Hanya guru yang bersangkutan yang dapat menilai proses supervisi ini.');
        }

        if (! in_array($cycle->status, self::OPEN_STATUSES, true)) {
            throw new DomainException('Penilaian proses supervisi hanya dapat diisi antara tahap umpan balik dan sebelum siklus dilaporkan.');
        }

        if (! SupervisionProcessSurvey::isComplete($answers)) {
            throw new DomainException('Semua dimensi penilaian wajib diisi (skala 1–4).');
        }

        $evaluation = SupervisorEvaluation::updateOrCreate(
            ['cycle_id' => $cycle->getKey()],
            [
                'guru_id' => $guru->getKey(),
                'supervisor_id' => $cycle->supervisor_id,
                'dinas_id' => $cycle->dinas_id,
                'sekolah_id' => $cycle->sekolah_id,
                'jawaban' => SupervisionProcessSurvey::normalize($answers),
                'komentar' => $komentar !== null && trim($komentar) !== '' ? trim($komentar) : null,
                'submitted_at' => now(),
            ],
        );

        // Audit tanpa konten jawaban — jejak keberadaan saja.
        $this->audit->log('accountability.evaluation_submitted', $cycle, actor: $guru);

        return $evaluation->refresh();
    }
}

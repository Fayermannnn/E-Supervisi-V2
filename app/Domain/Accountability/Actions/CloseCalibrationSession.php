<?php

declare(strict_types=1);

namespace App\Domain\Accountability\Actions;

use App\Domain\Accountability\CalibrationStats;
use App\Domain\Audit\AuditLogger;
use App\Models\CalibrationSession;
use App\Models\User;
use App\Support\Enums\Permission;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Menutup sesi kalibrasi, menghitung, dan menyimpan snapshot statistik
 * reliabilitas antar-penilai (M12).
 */
class CloseCalibrationSession
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function handle(User $actor, CalibrationSession $session): CalibrationSession
    {
        if (! $actor->can(Permission::ManageCalibration->value)
            || ($actor->isAdminDinas() && $actor->adminDinasId() !== $session->dinas_id)) {
            throw new AuthorizationException('Anda tidak berwenang menutup sesi ini.');
        }

        if ($session->status === CalibrationSession::STATUS_SELESAI) {
            return $session;
        }

        $submitted = $session->participants()->whereNotNull('submitted_at')->get();
        if ($submitted->count() < 2) {
            throw new DomainException('Butuh minimal dua penilai yang sudah mengirim skor.');
        }

        /** @var array<string, array<string, float>> $byRater */
        $byRater = [];
        foreach ($submitted as $participant) {
            $byRater[$participant->getKey()] = $participant->scores()
                ->get()
                ->mapWithKeys(static fn ($s): array => [$s->item_key => (float) $s->nilai])
                ->all();
        }

        $stats = CalibrationStats::compute($byRater);

        $session->forceFill([
            'status' => CalibrationSession::STATUS_SELESAI,
            'stats' => $stats,
            'closed_at' => now(),
        ])->save();

        $this->audit->log('calibration.session_closed', $session, new: [
            'persen_kesepakatan' => $stats['persen_kesepakatan'],
            'fleiss_kappa' => $stats['fleiss_kappa'],
        ], actor: $actor);

        return $session->refresh();
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Accountability\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\CalibrationParticipant;
use App\Models\CalibrationSession;
use App\Models\User;
use App\Support\Enums\Permission;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;

class AddCalibrationParticipant
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function handle(User $actor, CalibrationSession $session, User $supervisor): CalibrationParticipant
    {
        if (! $actor->can(Permission::ManageCalibration->value) || $this->outOfScope($actor, $session)) {
            throw new AuthorizationException('Anda tidak berwenang mengubah sesi ini.');
        }

        if ($session->status === CalibrationSession::STATUS_SELESAI) {
            throw new DomainException('Sesi kalibrasi sudah ditutup.');
        }

        if (! $supervisor->can(Permission::ParticipateCalibration->value)) {
            throw new DomainException('Pengguna yang ditambahkan bukan penilai (supervisor).');
        }

        $participant = $session->participants()->firstOrCreate(['supervisor_id' => $supervisor->getKey()]);

        if ($session->status === CalibrationSession::STATUS_DRAFT) {
            $session->forceFill(['status' => CalibrationSession::STATUS_BERJALAN])->save();
        }

        $this->audit->log('calibration.participant_added', $session, new: ['supervisor_id' => $supervisor->getKey()], actor: $actor);

        return $participant;
    }

    private function outOfScope(User $actor, CalibrationSession $session): bool
    {
        return $actor->isAdminDinas() && $actor->adminDinasId() !== $session->dinas_id;
    }
}

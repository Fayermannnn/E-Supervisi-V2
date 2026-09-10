<?php

declare(strict_types=1);

namespace App\Domain\Accountability\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\CalibrationSession;
use App\Models\InstrumentVersion;
use App\Models\User;
use App\Support\Enums\Permission;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Membuat sesi kalibrasi antar-penilai (M12, @provisional).
 */
class CreateCalibrationSession
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{instrument_version_id: string, judul: string, deskripsi?: string|null, observation_id?: string|null, artefak_url?: string|null, dinas_id?: string|null}  $data
     *
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function handle(User $actor, array $data): CalibrationSession
    {
        if (! $actor->can(Permission::ManageCalibration->value)) {
            throw new AuthorizationException('Anda tidak berwenang mengelola kalibrasi.');
        }

        $dinasId = $actor->isAdminDinas() ? $actor->adminDinasId() : ($data['dinas_id'] ?? null);
        if ($dinasId === null) {
            throw new DomainException('Dinas untuk sesi kalibrasi wajib ditentukan.');
        }

        if (InstrumentVersion::query()->whereKey($data['instrument_version_id'])->doesntExist()) {
            throw new DomainException('Versi instrumen tidak ditemukan.');
        }

        $session = CalibrationSession::create([
            'dinas_id' => $dinasId,
            'instrument_version_id' => $data['instrument_version_id'],
            'observation_id' => $data['observation_id'] ?? null,
            'judul' => $data['judul'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'artefak_url' => $data['artefak_url'] ?? null,
            'status' => CalibrationSession::STATUS_DRAFT,
            'dibuat_oleh' => $actor->getKey(),
        ]);

        $this->audit->log('calibration.session_created', $session, new: ['judul' => $session->judul], actor: $actor);

        return $session;
    }
}

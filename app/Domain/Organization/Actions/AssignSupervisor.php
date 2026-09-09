<?php

declare(strict_types=1);

namespace App\Domain\Organization\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Support\Enums\SupervisorType;
use DomainException;

class AssignSupervisor
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @throws DomainException bila penugasan tumpang tindih atau melanggar lingkup
     */
    public function handle(User $actor, User $supervisor, User $guru, string $mulai, ?string $selesai = null): SupervisorAssignment
    {
        // Kepala sekolah hanya membina guru di sekolahnya.
        if ($supervisor->supervisor_type === SupervisorType::KepalaSekolah
            && $supervisor->sekolah_id !== $guru->sekolah_id) {
            throw new DomainException('Kepala sekolah hanya dapat membina guru di sekolahnya.');
        }

        // Pengawas: guru harus berada di dinas yang sama.
        if ($supervisor->resolveDinasId() !== $guru->resolveDinasId()) {
            throw new DomainException('Supervisor dan guru harus berada di dinas yang sama.');
        }

        $overlaps = SupervisorAssignment::query()
            ->where('supervisor_id', $supervisor->id)
            ->where('guru_id', $guru->id)
            ->where(function ($q) use ($mulai, $selesai): void {
                $q->whereNull('selesai')
                    ->orWhereDate('selesai', '>=', $mulai);
                if ($selesai !== null) {
                    $q->whereDate('mulai', '<=', $selesai);
                }
            })
            ->exists();

        if ($overlaps) {
            throw new DomainException('Sudah ada penugasan aktif untuk pasangan supervisor–guru ini pada periode tersebut.');
        }

        $assignment = SupervisorAssignment::create([
            'supervisor_id' => $supervisor->id,
            'guru_id' => $guru->id,
            'created_by' => $actor->id,
            'mulai' => $mulai,
            'selesai' => $selesai,
        ]);

        $this->audit->log('supervisor_assignment.created', $assignment, new: [
            'supervisor_id' => $supervisor->id,
            'guru_id' => $guru->id,
            'mulai' => $mulai,
        ], actor: $actor);

        return $assignment;
    }
}

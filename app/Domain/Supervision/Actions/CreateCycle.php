<?php

declare(strict_types=1);

namespace App\Domain\Supervision\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\SupervisionCycle;
use App\Models\SupervisorAssignment;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreateCycle
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @throws DomainException bila guru bukan binaan aktif supervisor
     */
    public function handle(
        User $supervisor,
        User $guru,
        string $tahunAjaran,
        string $semester,
        string $judul,
        ?string $fokusRingkas = null,
        ?string $programId = null,
    ): SupervisionCycle {
        $isBinaan = SupervisorAssignment::query()
            ->where('supervisor_id', $supervisor->getKey())
            ->where('guru_id', $guru->getKey())
            ->activeOn()
            ->exists();

        if (! $isBinaan) {
            throw new DomainException('Guru tersebut bukan binaan aktif Anda.');
        }

        if ($guru->sekolah_id === null) {
            throw new DomainException('Guru belum ditempatkan pada sekolah.');
        }

        return DB::transaction(function () use ($supervisor, $guru, $tahunAjaran, $semester, $judul, $fokusRingkas, $programId): SupervisionCycle {
            $cycle = SupervisionCycle::create([
                'guru_id' => $guru->getKey(),
                'supervisor_id' => $supervisor->getKey(),
                'sekolah_id' => $guru->sekolah_id,
                'dinas_id' => $guru->resolveDinasId(),
                'program_id' => $programId,
                'tahun_ajaran' => $tahunAjaran,
                'semester' => $semester,
                'judul' => $judul,
                'fokus_ringkas' => $fokusRingkas,
                'status' => CycleStatus::Draft,
            ]);

            $this->audit->log('cycle.created', $cycle, new: [
                'guru_id' => $guru->getKey(),
                'judul' => $judul,
            ], actor: $supervisor);

            return $cycle;
        });
    }
}

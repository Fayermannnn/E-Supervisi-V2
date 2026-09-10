<?php

declare(strict_types=1);

namespace App\Domain\Program\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Supervision\Actions\CreateCycle;
use App\Models\AnnualProgram;
use App\Models\ProgramTarget;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Menyemai siklus supervisi (status DRAFT) secara massal dari daftar target
 * program yang belum memiliki siklus (M7). Perencanaan, pemilihan instrumen,
 * dan kesepakatan tetap manual per siklus lewat jalur Fase 2.
 */
class GenerateProgramCycles
{
    public function __construct(
        private readonly CreateCycle $createCycle,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array{dibuat: int, dilewati: list<array{guru: string, alasan: string}>}
     *
     * @throws DomainException
     */
    public function handle(User $owner, AnnualProgram $program): array
    {
        if ($program->owner_id !== $owner->getKey()) {
            throw new DomainException('Program ini bukan milik Anda.');
        }

        $pending = $program->targets()->with('guru')->whereNull('cycle_id')->get();
        if ($pending->isEmpty()) {
            throw new DomainException('Tidak ada target yang perlu di-generate.');
        }

        $dibuat = 0;
        $dilewati = [];

        DB::transaction(function () use ($owner, $program, $pending, &$dibuat, &$dilewati): void {
            /** @var ProgramTarget $target */
            foreach ($pending as $target) {
                $guru = $target->guru()->first();
                if ($guru === null) {
                    $dilewati[] = ['guru' => $target->guru_id, 'alasan' => 'Guru tidak ditemukan.'];

                    continue;
                }

                try {
                    $cycle = $this->createCycle->handle(
                        supervisor: $owner,
                        guru: $guru,
                        tahunAjaran: $program->tahun_ajaran,
                        semester: $program->semester,
                        judul: 'Supervisi Pembelajaran '.$guru->name,
                        fokusRingkas: $target->fokus_ringkas,
                        programId: $program->getKey(),
                    );
                } catch (Throwable $e) {
                    $dilewati[] = ['guru' => $guru->name, 'alasan' => $e->getMessage()];

                    continue;
                }

                $target->forceFill(['cycle_id' => $cycle->getKey(), 'generated_at' => now()])->save();
                $dibuat++;
            }

            if ($dibuat > 0 && $program->status === AnnualProgram::STATUS_DRAFT) {
                $program->forceFill(['status' => AnnualProgram::STATUS_AKTIF])->save();
            }

            $this->audit->log('program.cycles_generated', $program, new: [
                'dibuat' => $dibuat,
                'dilewati' => count($dilewati),
            ], actor: $owner);
        });

        return ['dibuat' => $dibuat, 'dilewati' => $dilewati];
    }
}

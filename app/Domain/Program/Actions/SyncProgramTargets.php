<?php

declare(strict_types=1);

namespace App\Domain\Program\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\AnnualProgram;
use App\Models\SupervisorAssignment;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Menyelaraskan daftar guru target sebuah program dengan daftar yang dikirim
 * supervisor. Target yang sudah menghasilkan siklus tidak dihapus (M7).
 */
class SyncProgramTargets
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  list<array{guru_id: string, fokus_ringkas?: string|null, rencana_mulai?: string|null, rencana_selesai?: string|null}>  $targets
     *
     * @throws DomainException
     */
    public function handle(User $owner, AnnualProgram $program, array $targets): AnnualProgram
    {
        if ($program->owner_id !== $owner->getKey()) {
            throw new DomainException('Program ini bukan milik Anda.');
        }

        if (! $program->isMutable()) {
            throw new DomainException('Program tidak dapat diubah pada status ini.');
        }

        $guruIds = array_values(array_unique(array_map(static fn (array $t): string => $t['guru_id'], $targets)));

        $binaanIds = SupervisorAssignment::query()
            ->where('supervisor_id', $owner->getKey())
            ->whereIn('guru_id', $guruIds)
            ->activeOn()
            ->pluck('guru_id')
            ->all();

        $notBinaan = array_diff($guruIds, $binaanIds);
        if ($notBinaan !== []) {
            throw new DomainException('Sebagian guru bukan binaan aktif Anda.');
        }

        return DB::transaction(function () use ($owner, $program, $targets, $guruIds): AnnualProgram {
            foreach ($targets as $t) {
                $program->targets()->updateOrCreate(
                    ['guru_id' => $t['guru_id']],
                    [
                        'fokus_ringkas' => $t['fokus_ringkas'] ?? null,
                        'rencana_mulai' => $t['rencana_mulai'] ?? null,
                        'rencana_selesai' => $t['rencana_selesai'] ?? null,
                    ],
                );
            }

            // Hapus target yang dicabut — hanya bila belum menghasilkan siklus.
            $program->targets()
                ->whereNotIn('guru_id', $guruIds)
                ->whereNull('cycle_id')
                ->delete();

            $this->audit->log('program.targets_synced', $program, new: ['jumlah' => count($guruIds)], actor: $owner);

            return $program->refresh();
        });
    }
}

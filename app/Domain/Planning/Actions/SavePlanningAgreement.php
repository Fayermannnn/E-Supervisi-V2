<?php

declare(strict_types=1);

namespace App\Domain\Planning\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\InstrumentVersion;
use App\Models\PlanningAgreement;
use App\Models\SupervisionCycle;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use DomainException;
use Illuminate\Support\Facades\DB;

class SavePlanningAgreement
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data  fokus_observasi, tujuan?, instrument_version_id,
     *                                      tipe_observasi, jadwal_mulai, jadwal_selesai?, lokasi?, kelas?, mata_pelajaran?
     *
     * @throws DomainException
     */
    public function handle(User $actor, SupervisionCycle $cycle, array $data): PlanningAgreement
    {
        if ($cycle->status !== CycleStatus::Draft) {
            throw new DomainException('Kesepakatan hanya dapat disunting saat siklus berstatus Draf.');
        }

        $version = InstrumentVersion::query()
            ->with('instrument')
            ->whereKey($data['instrument_version_id'])
            ->firstOrFail();

        if (! $version->isPublished()) {
            throw new DomainException('Versi instrumen belum diterbitkan.');
        }

        return DB::transaction(function () use ($actor, $cycle, $data, $version): PlanningAgreement {
            $existing = $cycle->planningAgreement()->first();
            $changingCore = $existing === null
                || $existing->instrument_version_id !== $version->getKey()
                || $existing->fokus_observasi !== $data['fokus_observasi'];

            $agreement = PlanningAgreement::updateOrCreate(
                ['cycle_id' => $cycle->getKey()],
                [
                    'fokus_observasi' => $data['fokus_observasi'],
                    'tujuan' => $data['tujuan'] ?? null,
                    'instrument_id' => $version->instrument_id,
                    'instrument_version_id' => $version->getKey(),
                    'tipe_observasi' => $data['tipe_observasi'],
                    'jadwal_mulai' => $data['jadwal_mulai'],
                    'jadwal_selesai' => $data['jadwal_selesai'] ?? null,
                    'lokasi' => $data['lokasi'] ?? null,
                    'kelas' => $data['kelas'] ?? null,
                    'mata_pelajaran' => $data['mata_pelajaran'] ?? null,
                ],
            );

            // Perubahan substantif membatalkan kesepakatan sebelumnya.
            if ($changingCore) {
                $agreement->forceFill([
                    'disepakati_guru_at' => null,
                    'disepakati_supervisor_at' => null,
                ])->save();
            }

            $cycle->update(['fokus_ringkas' => $data['fokus_observasi']]);

            $this->audit->log('planning_agreement.saved', $cycle, new: [
                'instrument_version_id' => $version->getKey(),
                'jadwal_mulai' => $data['jadwal_mulai'],
            ], actor: $actor);

            return $agreement;
        });
    }
}

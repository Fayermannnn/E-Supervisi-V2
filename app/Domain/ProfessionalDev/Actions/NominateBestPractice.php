<?php

declare(strict_types=1);

namespace App\Domain\ProfessionalDev\Actions;

use App\Domain\Administration\PolicySettings;
use App\Domain\Audit\AuditLogger;
use App\Domain\ProfessionalDev\Notifications\BestPracticeStatusNotification;
use App\Models\BestPractice;
use App\Models\SupervisionCycle;
use App\Models\User;
use App\Support\Enums\CycleStatus;
use App\Support\Enums\Permission;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Supervisor menominasikan siklus berskor tinggi ke Perpustakaan Praktik Baik
 * (M10, @provisional). Butuh persetujuan guru (UU PDP) sebelum masuk antrean
 * kurasi dinas.
 */
class NominateBestPractice
{
    public function __construct(
        private readonly PolicySettings $policy,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{judul: string, ringkasan: string, praktik: string, tags?: list<string>, anonim?: bool}  $data
     *
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function handle(User $actor, SupervisionCycle $cycle, array $data): BestPractice
    {
        if (! $actor->can(Permission::NominateBestPractice->value) || $cycle->supervisor_id !== $actor->getKey()) {
            throw new AuthorizationException('Hanya supervisor siklus yang dapat menominasikan praktik baik.');
        }

        if (! in_array($cycle->status, [CycleStatus::Reported, CycleStatus::Archived], true)) {
            throw new DomainException('Siklus harus sudah dilaporkan sebelum dinominasikan.');
        }

        $summary = DB::table('analysis_results')->where('cycle_id', $cycle->getKey())->value('score_summary');
        $decoded = is_string($summary) ? json_decode($summary, true) : $summary;
        $total = is_array($decoded) ? ($decoded['total'] ?? null) : null;
        $band = is_array($decoded) ? ($decoded['band'] ?? null) : null;

        $minScore = (float) $this->policy->get('professional_dev.best_practice_min_score', $cycle->dinas_id);
        if ($total === null || (float) $total < $minScore) {
            throw new DomainException('Skor siklus belum memenuhi ambang minimal untuk praktik baik.');
        }

        $existing = BestPractice::where('cycle_id', $cycle->getKey())->first();
        if ($existing !== null && ! in_array($existing->status, [BestPractice::STATUS_DITOLAK, BestPractice::STATUS_DITARIK], true)) {
            throw new DomainException('Siklus ini sudah memiliki entri praktik baik yang aktif.');
        }

        return DB::transaction(function () use ($actor, $cycle, $data, $band, $existing): BestPractice {
            $attributes = [
                'guru_id' => $cycle->guru_id,
                'dinas_id' => $cycle->dinas_id,
                'sekolah_id' => $cycle->sekolah_id,
                'nominated_by' => $actor->getKey(),
                'judul' => $data['judul'],
                'ringkasan' => $data['ringkasan'],
                'praktik' => $data['praktik'],
                'tags' => $data['tags'] ?? [],
                'skor_band' => $band !== null ? (string) $band : null,
                'anonim' => (bool) ($data['anonim'] ?? false),
                'status' => BestPractice::STATUS_MENUNGGU_CONSENT,
                'consent_by' => null,
                'consent_at' => null,
                'curated_by' => null,
                'curated_at' => null,
                'catatan_kurasi' => null,
                'terbit_at' => null,
            ];

            $bp = $existing !== null
                ? tap($existing)->update($attributes)
                : BestPractice::create(['cycle_id' => $cycle->getKey(), ...$attributes]);

            $this->audit->log('best_practice.nominated', $bp, new: ['judul' => $bp->judul], actor: $actor);

            $guru = $cycle->guru()->first();
            if ($guru !== null) {
                Notification::send($guru, new BestPracticeStatusNotification(
                    $bp,
                    'Permintaan persetujuan praktik baik',
                    "Supervisor Anda mengusulkan praktik pembelajaran dari siklus \"{$cycle->judul}\" untuk Perpustakaan Praktik Baik. Persetujuan Anda diperlukan.",
                    route('cycles.show', $cycle),
                ));
            }

            return $bp->refresh();
        });
    }
}

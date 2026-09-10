<?php

declare(strict_types=1);

namespace App\Domain\ProfessionalDev\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\PkbRecommendation;
use App\Models\User;
use App\Support\Enums\Permission;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Guru merespons rekomendasi PKB (dipilih/ditolak); supervisor/guru menandai
 * selesai setelah kegiatan dilaksanakan (M9, @provisional).
 */
class RespondPkbRecommendation
{
    private const TRANSITIONS = [
        PkbRecommendation::STATUS_DISARANKAN => [PkbRecommendation::STATUS_DIPILIH, PkbRecommendation::STATUS_DITOLAK],
        PkbRecommendation::STATUS_DIPILIH => [PkbRecommendation::STATUS_SELESAI, PkbRecommendation::STATUS_DITOLAK],
    ];

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function handle(User $actor, PkbRecommendation $recommendation, string $status): PkbRecommendation
    {
        $cycle = $recommendation->cycle()->sole();

        $isGuru = $actor->getKey() === $cycle->guru_id;
        $isSupervisor = $actor->getKey() === $cycle->supervisor_id;

        if (! $isGuru && ! $isSupervisor) {
            throw new AuthorizationException('Rekomendasi ini bukan milik Anda.');
        }

        if ($isGuru && ! $actor->can(Permission::RespondPkbRecommendation->value)) {
            throw new AuthorizationException('Anda tidak berwenang merespons rekomendasi PKB.');
        }

        $allowed = self::TRANSITIONS[$recommendation->status] ?? [];
        if (! in_array($status, $allowed, true)) {
            throw new DomainException("Tidak dapat mengubah rekomendasi dari '{$recommendation->status}' ke '{$status}'.");
        }

        $from = $recommendation->status;
        $recommendation->forceFill(['status' => $status, 'direspons_at' => now()])->save();
        $this->audit->log('pkb.recommendation_responded', $cycle, old: ['status' => $from], new: ['status' => $status], actor: $actor);

        return $recommendation->refresh();
    }
}

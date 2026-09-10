<?php

declare(strict_types=1);

namespace App\Domain\ProfessionalDev\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\BestPractice;
use App\Models\User;
use App\Support\Enums\Permission;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Menarik entri praktik baik dari peredaran (nominator atau Admin Dinas).
 * Guru juga dapat menarik persetujuannya kapan pun (UU PDP).
 */
class WithdrawBestPractice
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function handle(User $actor, BestPractice $bestPractice, ?string $alasan = null): BestPractice
    {
        $isNominator = $actor->getKey() === $bestPractice->nominated_by;
        $isGuru = $actor->getKey() === $bestPractice->guru_id;
        $isCurator = $actor->can(Permission::CurateBestPractice->value) && $actor->adminDinasId() === $bestPractice->dinas_id;

        if (! $isNominator && ! $isCurator && ! $isGuru) {
            throw new AuthorizationException('Anda tidak berwenang menarik entri ini.');
        }

        if (in_array($bestPractice->status, [BestPractice::STATUS_DITOLAK, BestPractice::STATUS_DITARIK], true)) {
            throw new DomainException('Entri ini sudah tidak aktif.');
        }

        $bestPractice->forceFill([
            'status' => BestPractice::STATUS_DITARIK,
            'catatan_kurasi' => $alasan ?? $bestPractice->catatan_kurasi,
        ])->save();

        $this->audit->log('best_practice.withdrawn', $bestPractice, context: ['alasan' => $alasan], actor: $actor);

        return $bestPractice->refresh();
    }
}

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
 * Guru menyetujui / menolak publikasi praktik baiknya (M10 — basis pemrosesan
 * data pribadi, UU 27/2022).
 */
class RespondBestPracticeConsent
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function handle(User $guru, BestPractice $bestPractice, bool $setuju): BestPractice
    {
        if ($bestPractice->guru_id !== $guru->getKey() || ! $guru->can(Permission::RespondBestPracticeConsent->value)) {
            throw new AuthorizationException('Anda tidak berwenang menanggapi permintaan ini.');
        }

        if ($bestPractice->status !== BestPractice::STATUS_MENUNGGU_CONSENT) {
            throw new DomainException('Permintaan persetujuan ini sudah tidak aktif.');
        }

        if ($setuju) {
            $bestPractice->forceFill([
                'status' => BestPractice::STATUS_MENUNGGU_KURASI,
                'consent_by' => $guru->getKey(),
                'consent_at' => now(),
            ])->save();
            $this->audit->log('best_practice.consent_granted', $bestPractice, actor: $guru);
        } else {
            $bestPractice->forceFill([
                'status' => BestPractice::STATUS_DITOLAK,
                'consent_by' => $guru->getKey(),
                'consent_at' => now(),
            ])->save();
            $this->audit->log('best_practice.consent_declined', $bestPractice, actor: $guru);
        }

        return $bestPractice->refresh();
    }
}

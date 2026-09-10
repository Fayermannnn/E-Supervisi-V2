<?php

declare(strict_types=1);

namespace App\Domain\Ai\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\AiGeneration;
use App\Models\User;
use App\Support\Enums\Permission;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;

/**
 * Peninjauan manusia atas draf AI (RULE 4). Menyetujui/menyunting/menolak.
 * Tanpa langkah ini, keluaran AI tidak pernah dapat dipakai sebagai data final.
 */
class ReviewAiGeneration
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  string  $decision  accept | edit | reject
     *
     * @throws AuthorizationException
     */
    public function handle(User $reviewer, AiGeneration $generation, string $decision, ?string $editedText = null): AiGeneration
    {
        if (! $reviewer->can(Permission::ReviewAiDraft->value)) {
            throw new AuthorizationException('Anda tidak berwenang meninjau draf AI.');
        }

        if ($generation->status !== AiGeneration::STATUS_DRAFT) {
            throw new DomainException('Draf belum siap ditinjau.');
        }

        $reviewStatus = match ($decision) {
            'accept' => AiGeneration::REVIEW_ACCEPTED,
            'edit' => AiGeneration::REVIEW_EDITED,
            'reject' => AiGeneration::REVIEW_REJECTED,
            default => throw new InvalidArgumentException("Keputusan tidak dikenal: {$decision}"),
        };

        $generation->forceFill([
            'review_status' => $reviewStatus,
            'reviewer_id' => $reviewer->getKey(),
            'reviewed_at' => now(),
            'reviewed_output' => $decision === 'edit' ? $editedText : null,
        ])->save();

        $this->audit->log('ai.draft_reviewed', $generation, new: ['review_status' => $reviewStatus], actor: $reviewer);

        return $generation;
    }
}

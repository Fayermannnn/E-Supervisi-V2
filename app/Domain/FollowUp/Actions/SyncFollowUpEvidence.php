<?php

declare(strict_types=1);

namespace App\Domain\FollowUp\Actions;

use App\Models\FollowUpItem;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Menerapkan batch bukti RTL dari antrean luring (ADR-006, docs/offline.md,
 * checkpoint R-04). Setiap entri didelegasikan ke `SubmitFollowUpEvidence`
 * yang sudah idempoten per UUID klien — modul ini hanya mengorkestrasi batch
 * & mengumpulkan hasil/kegagalan per entri (satu entri gagal tidak
 * menggagalkan entri lain, selaras `SyncObservations`).
 */
class SyncFollowUpEvidence
{
    public function __construct(private readonly SubmitFollowUpEvidence $submit) {}

    /**
     * @param  list<array<string, mixed>>  $batch
     * @return array{applied: list<array{id: string, follow_up_item_id: string, upload_status: string}>, failed: list<array{id: ?string, follow_up_item_id: ?string, message: string}>}
     */
    public function handle(User $guru, array $batch): array
    {
        $applied = [];
        $failed = [];

        foreach ($batch as $payload) {
            $itemId = (string) ($payload['follow_up_item_id'] ?? '');
            $id = isset($payload['id']) ? (string) $payload['id'] : null;

            try {
                $item = FollowUpItem::query()->whereKey($itemId)->firstOrFail();

                $evidence = $this->submit->handle($guru, $item, [
                    'id' => $id,
                    'tipe' => (string) ($payload['tipe'] ?? 'catatan'),
                    'deskripsi' => $payload['deskripsi'] ?? null,
                    'url' => $payload['url'] ?? null,
                ]);

                $applied[] = [
                    'id' => $evidence->id,
                    'follow_up_item_id' => $itemId,
                    'upload_status' => $evidence->upload_status,
                ];
            } catch (AuthorizationException|ModelNotFoundException $e) {
                $failed[] = ['id' => $id, 'follow_up_item_id' => $itemId ?: null, 'message' => $e->getMessage()];
            }
        }

        return ['applied' => $applied, 'failed' => $failed];
    }
}

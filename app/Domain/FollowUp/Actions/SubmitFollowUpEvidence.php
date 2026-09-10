<?php

declare(strict_types=1);

namespace App\Domain\FollowUp\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\FollowUpEvidence;
use App\Models\FollowUpItem;
use App\Models\User;
use App\Support\Enums\Permission;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Guru mengunggah bukti pelaksanaan RTL (M5). ID bukti di-generate klien agar
 * antrean luring idempoten (R-04). Bukti masuk menandai butir sebagai berjalan;
 * bila semua butir selesai, plan ditutup.
 */
class SubmitFollowUpEvidence
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{id?: ?string, tipe: string, deskripsi?: ?string, url?: ?string, disk?: ?string, path?: ?string, original_name?: ?string}  $data
     *
     * @throws AuthorizationException
     */
    public function handle(User $guru, FollowUpItem $item, array $data): FollowUpEvidence
    {
        $plan = $item->plan()->with('cycle')->sole();
        $cycle = $plan->cycle;

        if (! $guru->can(Permission::UploadFollowUpEvidence->value) || $cycle === null || $cycle->guru_id !== $guru->getKey()) {
            throw new AuthorizationException('Anda tidak berwenang mengunggah bukti untuk RTL ini.');
        }

        $id = $data['id'] ?? (string) Str::uuid7();

        $existing = FollowUpEvidence::query()->whereKey($id)->first();
        if ($existing !== null) {
            return $existing; // idempoten
        }

        return DB::transaction(function () use ($guru, $item, $cycle, $data, $id): FollowUpEvidence {
            $evidence = FollowUpEvidence::create([
                'id' => $id,
                'follow_up_item_id' => $item->getKey(),
                'diunggah_oleh' => $guru->getKey(),
                'tipe' => $data['tipe'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'url' => $data['url'] ?? null,
                'disk' => $data['disk'] ?? null,
                'path' => $data['path'] ?? null,
                'original_name' => $data['original_name'] ?? null,
                'upload_status' => isset($data['path']) || isset($data['url']) ? 'stored' : 'pending',
                'captured_at' => now(),
            ]);

            if ($item->status === FollowUpItem::STATUS_BELUM) {
                $item->update(['status' => FollowUpItem::STATUS_BERJALAN]);
            }

            $this->audit->log('followup.evidence_submitted', $cycle, context: ['item' => $item->getKey()], actor: $guru);

            return $evidence;
        });
    }
}

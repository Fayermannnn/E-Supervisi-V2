<?php

declare(strict_types=1);

namespace App\Domain\FollowUp\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\FollowUpItem;
use App\Models\FollowUpPlan;
use App\Models\User;
use App\Support\Enums\Permission;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class UpdateFollowUpItem
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  string  $status  belum | berjalan | selesai
     *
     * @throws AuthorizationException
     */
    public function handle(User $actor, FollowUpItem $item, string $status): FollowUpItem
    {
        $plan = $item->plan()->with('cycle')->sole();
        $cycle = $plan->cycle;

        $isSupervisor = $cycle !== null && $cycle->supervisor_id === $actor->getKey();
        $isGuru = $cycle !== null && $cycle->guru_id === $actor->getKey();

        if (! $actor->can(Permission::UpdateFollowUp->value) || (! $isSupervisor && ! $isGuru)) {
            throw new AuthorizationException('Anda tidak berwenang memperbarui RTL ini.');
        }

        return DB::transaction(function () use ($actor, $item, $plan, $status): FollowUpItem {
            $item->update([
                'status' => $status,
                'selesai_at' => $status === FollowUpItem::STATUS_SELESAI ? now() : null,
            ]);

            // Semua butir selesai -> plan selesai.
            $allDone = $plan->items()->where('status', '!=', FollowUpItem::STATUS_SELESAI)->doesntExist();
            if ($allDone && $plan->isOpen()) {
                $plan->update(['status' => FollowUpPlan::STATUS_SELESAI, 'selesai_at' => now()]);
                $this->audit->log('followup.plan_completed', $plan->cycle()->sole(), actor: $actor);
            }

            return $item->refresh();
        });
    }
}

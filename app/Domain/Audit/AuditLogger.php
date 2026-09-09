<?php

declare(strict_types=1);

namespace App\Domain\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Satu-satunya pintu penulisan audit log (M16, ADR-010).
 *
 * Append-only: hanya membuat baris, tidak pernah update/hapus.
 */
class AuditLogger
{
    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @param  array<string, mixed>  $context
     */
    public function log(
        string $action,
        ?Model $auditable = null,
        array $old = [],
        array $new = [],
        array $context = [],
        ?User $actor = null,
    ): AuditLog {
        $actor ??= $this->resolveActor();
        $request = request();

        return AuditLog::create([
            'actor_id' => $actor?->getKey(),
            'actor_role' => $actor?->roles()->map(fn ($r) => $r->value)->join(','),
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $old === [] ? null : $old,
            'new_values' => $new === [] ? null : $new,
            'ip_address' => $request->getClientIp(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, '') ?: null,
            'context' => $context === [] ? null : $context,
        ]);
    }

    private function resolveActor(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}

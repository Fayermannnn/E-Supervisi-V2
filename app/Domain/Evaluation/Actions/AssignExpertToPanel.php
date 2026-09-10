<?php

declare(strict_types=1);

namespace App\Domain\Evaluation\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\EvaluationPanel;
use App\Models\PanelExpert;
use App\Models\User;
use App\Support\Enums\Permission;
use App\Support\Enums\Role;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;

class AssignExpertToPanel
{
    private const RUMPUN = [
        PanelExpert::RUMPUN_MANAJEMEN,
        PanelExpert::RUMPUN_SISTEM_INFORMASI,
        PanelExpert::RUMPUN_LAINNYA,
    ];

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function handle(User $actor, EvaluationPanel $panel, User $expert, string $rumpun, ?string $afiliasi = null): PanelExpert
    {
        if (! $actor->can(Permission::ManageEvaluationPanel->value)) {
            throw new AuthorizationException('Anda tidak berwenang mengelola panel evaluasi.');
        }

        if ($panel->status === EvaluationPanel::STATUS_SELESAI) {
            throw new DomainException('Panel sudah ditutup.');
        }

        if (! in_array($rumpun, self::RUMPUN, true)) {
            throw new DomainException('Rumpun ahli tidak dikenal.');
        }

        if (! $expert->hasRole(Role::Ahli)) {
            $expert->assignRole(Role::Ahli, assignedBy: $actor);
        }

        $panelExpert = $panel->experts()->firstOrCreate(
            ['user_id' => $expert->getKey()],
            ['rumpun' => $rumpun, 'afiliasi' => $afiliasi, 'diundang_at' => now()],
        );

        if ($panel->status === EvaluationPanel::STATUS_DRAFT) {
            $panel->forceFill(['status' => EvaluationPanel::STATUS_BERJALAN])->save();
        }

        $this->audit->log('evaluation.expert_assigned', $panel, new: [
            'user_id' => $expert->getKey(),
            'rumpun' => $rumpun,
        ], actor: $actor);

        return $panelExpert;
    }
}

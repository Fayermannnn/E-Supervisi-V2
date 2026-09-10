<?php

declare(strict_types=1);

namespace App\Domain\Evaluation\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\EvaluationPanel;
use App\Models\User;
use App\Support\Enums\Permission;
use Illuminate\Auth\Access\AuthorizationException;

class CreateEvaluationPanel
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{judul: string, artefak_versi: string, deskripsi?: string|null}  $data
     *
     * @throws AuthorizationException
     */
    public function handle(User $actor, array $data): EvaluationPanel
    {
        if (! $actor->can(Permission::ManageEvaluationPanel->value)) {
            throw new AuthorizationException('Anda tidak berwenang mengelola panel evaluasi.');
        }

        $panel = EvaluationPanel::create([
            'judul' => $data['judul'],
            'artefak_versi' => $data['artefak_versi'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'status' => EvaluationPanel::STATUS_DRAFT,
            'dibuat_oleh' => $actor->getKey(),
        ]);

        $this->audit->log('evaluation.panel_created', $panel, new: ['judul' => $panel->judul], actor: $actor);

        return $panel;
    }
}

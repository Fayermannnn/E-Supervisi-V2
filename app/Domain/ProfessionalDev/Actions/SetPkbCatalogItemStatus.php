<?php

declare(strict_types=1);

namespace App\Domain\ProfessionalDev\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\PkbCatalogItem;
use App\Models\User;
use App\Support\Enums\Permission;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;

class SetPkbCatalogItemStatus
{
    private const VALID = [
        PkbCatalogItem::STATUS_DRAFT,
        PkbCatalogItem::STATUS_TERBIT,
        PkbCatalogItem::STATUS_ARSIP,
    ];

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function handle(User $actor, PkbCatalogItem $item, string $status): PkbCatalogItem
    {
        if (! $actor->can(Permission::ManagePkbCatalog->value)) {
            throw new AuthorizationException('Anda tidak berwenang mengelola katalog PKB.');
        }

        if ($actor->isAdminDinas() && $item->pemilik_dinas_id !== $actor->adminDinasId()) {
            throw new AuthorizationException('Item ini di luar lingkup dinas Anda.');
        }

        if (! in_array($status, self::VALID, true)) {
            throw new DomainException("Status katalog tidak dikenal: {$status}.");
        }

        $from = $item->status;
        $item->forceFill(['status' => $status])->save();
        $this->audit->log('pkb_catalog.status_changed', $item, old: ['status' => $from], new: ['status' => $status], actor: $actor);

        return $item->refresh();
    }
}

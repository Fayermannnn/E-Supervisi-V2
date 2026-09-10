<?php

declare(strict_types=1);

namespace App\Domain\ProfessionalDev\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\PkbCatalogItem;
use App\Models\User;
use App\Support\Enums\Permission;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Membuat / memperbarui item Katalog PKB (M9, @provisional).
 * admin_dinas: item terikat dinasnya. admin_sistem: dapat membuat item global.
 */
class SavePkbCatalogItem
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{judul: string, deskripsi: string, tipe: string, penyelenggara?: string|null, tautan?: string|null, durasi_jam?: int|null, tags?: list<string>, kompetensi?: list<string>}  $data
     *
     * @throws AuthorizationException
     */
    public function handle(User $actor, ?PkbCatalogItem $item, array $data): PkbCatalogItem
    {
        if (! $actor->can(Permission::ManagePkbCatalog->value)) {
            throw new AuthorizationException('Anda tidak berwenang mengelola katalog PKB.');
        }

        $dinasId = $actor->isAdminDinas() ? $actor->adminDinasId() : ($item?->pemilik_dinas_id);

        if ($item !== null && $actor->isAdminDinas() && $item->pemilik_dinas_id !== $actor->adminDinasId()) {
            throw new AuthorizationException('Item ini di luar lingkup dinas Anda.');
        }

        $attributes = [
            'judul' => $data['judul'],
            'deskripsi' => $data['deskripsi'],
            'tipe' => $data['tipe'],
            'penyelenggara' => $data['penyelenggara'] ?? null,
            'tautan' => $data['tautan'] ?? null,
            'durasi_jam' => $data['durasi_jam'] ?? null,
            'tags' => $data['tags'] ?? [],
            'kompetensi' => $data['kompetensi'] ?? [],
        ];

        if ($item === null) {
            $item = PkbCatalogItem::create([
                ...$attributes,
                'pemilik_dinas_id' => $dinasId,
                'created_by' => $actor->getKey(),
            ]);
            $this->audit->log('pkb_catalog.created', $item, new: ['judul' => $item->judul], actor: $actor);
        } else {
            $item->fill($attributes)->save();
            $this->audit->log('pkb_catalog.updated', $item, new: ['judul' => $item->judul], actor: $actor);
        }

        return $item->refresh();
    }
}

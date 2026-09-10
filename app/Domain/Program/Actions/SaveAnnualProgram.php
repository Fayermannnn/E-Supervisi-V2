<?php

declare(strict_types=1);

namespace App\Domain\Program\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\AnnualProgram;
use App\Models\User;
use App\Support\Enums\Permission;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Membuat / memperbarui program supervisi tahunan milik supervisor (M7).
 */
class SaveAnnualProgram
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{judul: string, tahun_ajaran: string, semester: string, catatan?: string|null}  $data
     *
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function handle(User $owner, ?AnnualProgram $program, array $data): AnnualProgram
    {
        if (! $owner->can(Permission::ManageAnnualProgram->value)) {
            throw new AuthorizationException('Anda tidak berwenang mengelola program tahunan.');
        }

        if ($program !== null && $program->owner_id !== $owner->getKey()) {
            throw new AuthorizationException('Program ini bukan milik Anda.');
        }

        if ($program !== null && ! $program->isMutable()) {
            throw new DomainException('Program yang sudah selesai/dibatalkan tidak dapat diubah.');
        }

        $dinasId = $program !== null ? $program->dinas_id : $owner->resolveDinasId();
        if ($dinasId === null) {
            throw new DomainException('Akun Anda belum tertaut ke sebuah dinas.');
        }

        return DB::transaction(function () use ($owner, $program, $data, $dinasId): AnnualProgram {
            $attributes = [
                'judul' => $data['judul'],
                'tahun_ajaran' => $data['tahun_ajaran'],
                'semester' => $data['semester'],
                'catatan' => $data['catatan'] ?? null,
            ];

            if ($program === null) {
                $program = AnnualProgram::create([
                    'owner_id' => $owner->getKey(),
                    'dinas_id' => $dinasId,
                    ...$attributes,
                ]);
                $this->audit->log('program.created', $program, new: $attributes, actor: $owner);
            } else {
                $program->fill($attributes)->save();
                $this->audit->log('program.updated', $program, new: $attributes, actor: $owner);
            }

            return $program->refresh();
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Audit\Concerns;

use App\Domain\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Catat perubahan model ke audit log secara otomatis (ADR-010).
 *
 * Model yang memakai trait ini bisa mendefinisikan:
 *   protected array $auditExclude = ['updated_at', 'remember_token', ...];
 *
 * @mixin Model
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            app(AuditLogger::class)->log(
                action: $model->getTable().'.created',
                auditable: $model,
                new: self::auditableAttributes($model, $model->getAttributes()),
            );
        });

        static::updated(function (Model $model): void {
            $changed = self::auditableAttributes($model, $model->getChanges());

            if ($changed === []) {
                return;
            }

            $original = array_intersect_key($model->getOriginal(), $changed);

            app(AuditLogger::class)->log(
                action: $model->getTable().'.updated',
                auditable: $model,
                old: $original,
                new: $changed,
            );
        });

        static::deleted(function (Model $model): void {
            app(AuditLogger::class)->log(
                action: $model->getTable().'.deleted',
                auditable: $model,
            );
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private static function auditableAttributes(Model $model, array $attributes): array
    {
        /** @var list<string> $exclude */
        $exclude = array_merge(
            ['password', 'remember_token', 'updated_at', 'created_at'],
            property_exists($model, 'auditExclude') ? $model->auditExclude : [],
        );

        return array_diff_key($attributes, array_flip($exclude));
    }
}

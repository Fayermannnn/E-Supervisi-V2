<?php

declare(strict_types=1);

namespace App\Domain\Program\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\AnnualProgram;
use App\Models\User;
use DomainException;

/**
 * Transisi status administratif program (M7) — bukan state machine siklus.
 */
class SetProgramStatus
{
    /**
     * @var array<string, list<string>>
     */
    private const ALLOWED = [
        AnnualProgram::STATUS_DRAFT => [AnnualProgram::STATUS_AKTIF, AnnualProgram::STATUS_DIBATALKAN],
        AnnualProgram::STATUS_AKTIF => [AnnualProgram::STATUS_SELESAI, AnnualProgram::STATUS_DIBATALKAN],
    ];

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @throws DomainException
     */
    public function handle(User $owner, AnnualProgram $program, string $to): AnnualProgram
    {
        if ($program->owner_id !== $owner->getKey()) {
            throw new DomainException('Program ini bukan milik Anda.');
        }

        $allowed = self::ALLOWED[$program->status] ?? [];
        if (! in_array($to, $allowed, true)) {
            throw new DomainException("Tidak dapat mengubah status program dari '{$program->status}' ke '{$to}'.");
        }

        $from = $program->status;
        $program->forceFill(['status' => $to])->save();
        $this->audit->log('program.status_changed', $program, old: ['status' => $from], new: ['status' => $to], actor: $owner);

        return $program->refresh();
    }
}

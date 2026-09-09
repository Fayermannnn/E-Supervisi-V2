<?php

declare(strict_types=1);

namespace App\Domain\Planning\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\SupervisionCycle;
use App\Models\TeacherReflection;
use App\Models\User;

class SubmitReflection
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(User $guru, SupervisionCycle $cycle, string $tahap, string $konten): TeacherReflection
    {
        $reflection = TeacherReflection::updateOrCreate(
            ['cycle_id' => $cycle->getKey(), 'tahap' => $tahap],
            [
                'guru_id' => $guru->getKey(),
                'konten' => $konten,
                'submitted_at' => now(),
            ],
        );

        $this->audit->log('teacher_reflection.submitted', $cycle, context: ['tahap' => $tahap], actor: $guru);

        return $reflection;
    }
}

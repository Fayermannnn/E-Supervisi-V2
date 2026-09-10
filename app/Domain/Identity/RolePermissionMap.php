<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use App\Support\Enums\Permission;
use App\Support\Enums\Role;

/**
 * Peta peran → kemampuan kasar. Ini adalah representasi kode dari matriks
 * docs/rbac.md. Permission tidak berubah saat runtime pada MVP, jadi disimpan
 * di kode (bukan tabel) — diaudit lewat version control.
 *
 * Pembatasan per-record tetap di Policy + global scope.
 */
final class RolePermissionMap
{
    /**
     * @var array<string, list<Permission>>
     */
    private const MAP = [
        Role::Guru->value => [
            Permission::ManageOwnProfile,
            Permission::SubmitReflection,
            Permission::AgreePlanning,
            Permission::AcknowledgeFeedback,
            Permission::UpdateFollowUp,
            Permission::UploadFollowUpEvidence,
            Permission::RespondPkbRecommendation,
            Permission::RespondBestPracticeConsent,
            Permission::SubmitSupervisorEvaluation,
        ],

        Role::Supervisor->value => [
            Permission::ManageOwnProfile,
            Permission::CreateCycle,
            Permission::ScheduleCycle,
            Permission::CancelCycle,
            Permission::AgreePlanning,
            Permission::ConductObservation,
            Permission::SyncObservation,
            Permission::PerformAnalysis,
            Permission::FinalizeAnalysis,
            Permission::RequestAiDraft,
            Permission::ReviewAiDraft,
            Permission::RecordFeedback,
            Permission::SendFeedback,
            Permission::CreateFollowUp,
            Permission::UpdateFollowUp,
            Permission::CompileCycleReport,
            Permission::ExportReport,
            Permission::ViewAuditLog,
            Permission::ManageAnnualProgram,
            Permission::NominateBestPractice,
            Permission::ParticipateCalibration,
            Permission::ViewAccountabilityReport,
        ],

        Role::AdminDinas->value => [
            Permission::ManageOwnProfile,
            Permission::ManageOrganization,
            Permission::ManageAssignments,
            Permission::ManagePolicySettings,
            Permission::ManageInstruments,
            Permission::ViewAggregateReport,
            Permission::ExportReport,
            Permission::ViewAuditLog,
            Permission::ManagePkbCatalog,
            Permission::CurateBestPractice,
            Permission::ViewAccountabilityReport,
            Permission::ManageCalibration,
        ],

        Role::AdminSistem->value => [
            Permission::ManageOwnProfile,
            Permission::ManageUsers,
            Permission::ManageOrganization,
            Permission::ManageAssignments,
            Permission::ManagePolicySettings,
            Permission::ManageInstruments,
            Permission::ManageSupportTickets,
            Permission::ViewAuditLog,
            Permission::ManagePkbCatalog,
            Permission::ManageCalibration,
        ],
    ];

    /**
     * @return list<Permission>
     */
    public static function for(Role $role): array
    {
        return self::MAP[$role->value];
    }

    public static function grants(Role $role, Permission $permission): bool
    {
        return in_array($permission, self::MAP[$role->value], true);
    }
}

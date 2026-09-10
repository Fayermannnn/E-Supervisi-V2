<?php

declare(strict_types=1);

namespace App\Support\Enums;

/**
 * Kemampuan kasar per peran (coarse gate). Pembatasan per-record (guru hanya
 * datanya, supervisor hanya binaannya, admin dinas hanya dinasnya) ditangani
 * Policy + global scope, BUKAN di sini.
 *
 * Sumber: matriks docs/rbac.md — diverifikasi oleh tests/Feature/Rbac/MatrixTest.
 * Perubahan enum ini WAJIB disertai pembaruan matriks + test.
 */
enum Permission: string
{
    // Identitas & administrasi (Fase 1)
    case ManageOwnProfile = 'profile.manage_own';
    case ManageUsers = 'users.manage';
    case ManageOrganization = 'organization.manage';
    case ManageAssignments = 'assignments.manage';
    case ManagePolicySettings = 'config.manage';
    case ManageSupportTickets = 'support.manage_tickets';
    case ViewAuditLog = 'audit.view';

    // Instrumen (Fase 2)
    case ManageInstruments = 'instruments.manage';

    // Siklus — perencanaan & observasi (Fase 2)
    case CreateCycle = 'cycles.create';
    case ScheduleCycle = 'cycles.schedule';
    case CancelCycle = 'cycles.cancel';
    case SubmitReflection = 'reflections.submit';
    case AgreePlanning = 'planning.agree';
    case ConductObservation = 'observations.conduct';
    case SyncObservation = 'observations.sync';

    // Siklus — pasca-observasi (Fase 3, @provisional)
    case PerformAnalysis = 'analysis.perform';
    case FinalizeAnalysis = 'analysis.finalize';
    case RequestAiDraft = 'ai.request_draft';
    case ReviewAiDraft = 'ai.review_draft';
    case RecordFeedback = 'feedback.record';
    case SendFeedback = 'feedback.send';
    case AcknowledgeFeedback = 'feedback.acknowledge';
    case CreateFollowUp = 'followup.create';
    case UpdateFollowUp = 'followup.update';
    case UploadFollowUpEvidence = 'followup.upload_evidence';

    // Pelaporan (Fase 3, @provisional)
    case CompileCycleReport = 'reports.compile_cycle';
    case ViewAggregateReport = 'reports.view_aggregate';
    case ExportReport = 'reports.export';

    // Program tahunan (Fase 4 — M7, Confirmed)
    case ManageAnnualProgram = 'program.manage';

    // Pengembangan profesional (Fase 4 — M9/M10, @provisional)
    case ManagePkbCatalog = 'pkb.manage_catalog';
    case RespondPkbRecommendation = 'pkb.respond_recommendation';
    case NominateBestPractice = 'best_practice.nominate';
    case RespondBestPracticeConsent = 'best_practice.consent';
    case CurateBestPractice = 'best_practice.curate';

    // Akuntabilitas & kalibrasi (Fase 4 — M11/M12, @provisional)
    case SubmitSupervisorEvaluation = 'accountability.submit_evaluation';
    case ViewAccountabilityReport = 'accountability.view_report';
    case ManageCalibration = 'calibration.manage';
    case ParticipateCalibration = 'calibration.participate';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $p): string => $p->value, self::cases());
    }
}

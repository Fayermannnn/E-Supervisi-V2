<?php

declare(strict_types=1);

use App\Domain\Identity\RolePermissionMap;
use App\Support\Enums\Permission;
use App\Support\Enums\Role;

/**
 * Representasi kode dari matriks docs/rbac.md. Setiap sel diverifikasi.
 *
 * @return array<string, array{Role, Permission, bool}>
 */
dataset('rbac matrix', function () {
    $g = Role::Guru;
    $s = Role::Supervisor;
    $ad = Role::AdminDinas;
    $as = Role::AdminSistem;

    return [
        // Guru
        'guru dapat kelola profil' => [$g, Permission::ManageOwnProfile, true],
        'guru dapat isi refleksi' => [$g, Permission::SubmitReflection, true],
        'guru dapat unggah bukti RTL' => [$g, Permission::UploadFollowUpEvidence, true],
        'guru TIDAK dapat buat siklus' => [$g, Permission::CreateCycle, false],
        'guru TIDAK dapat kelola pengguna' => [$g, Permission::ManageUsers, false],
        'guru TIDAK dapat finalisasi analisis' => [$g, Permission::FinalizeAnalysis, false],
        'guru TIDAK dapat lihat audit' => [$g, Permission::ViewAuditLog, false],

        // Supervisor
        'supervisor dapat buat siklus' => [$s, Permission::CreateCycle, true],
        'supervisor dapat observasi' => [$s, Permission::ConductObservation, true],
        'supervisor dapat finalisasi analisis' => [$s, Permission::FinalizeAnalysis, true],
        'supervisor dapat kirim umpan balik' => [$s, Permission::SendFeedback, true],
        'supervisor dapat buat RTL' => [$s, Permission::CreateFollowUp, true],
        'supervisor TIDAK dapat kelola pengguna' => [$s, Permission::ManageUsers, false],
        'supervisor TIDAK dapat lihat laporan agregat' => [$s, Permission::ViewAggregateReport, false],
        'supervisor TIDAK dapat kelola organisasi' => [$s, Permission::ManageOrganization, false],

        // Admin Dinas
        'admin dinas dapat kelola organisasi' => [$ad, Permission::ManageOrganization, true],
        'admin dinas dapat kelola penugasan' => [$ad, Permission::ManageAssignments, true],
        'admin dinas dapat lihat laporan agregat' => [$ad, Permission::ViewAggregateReport, true],
        'admin dinas dapat kelola instrumen' => [$ad, Permission::ManageInstruments, true],
        'admin dinas TIDAK dapat buat siklus' => [$ad, Permission::CreateCycle, false],
        'admin dinas TIDAK dapat kelola pengguna' => [$ad, Permission::ManageUsers, false],
        'admin dinas TIDAK dapat observasi' => [$ad, Permission::ConductObservation, false],

        // Fase 4 — pengembangan profesional & akuntabilitas
        'supervisor dapat kelola program tahunan' => [$s, Permission::ManageAnnualProgram, true],
        'guru TIDAK dapat kelola program tahunan' => [$g, Permission::ManageAnnualProgram, false],
        'admin dinas TIDAK dapat kelola program tahunan' => [$ad, Permission::ManageAnnualProgram, false],
        'admin dinas dapat kelola katalog PKB' => [$ad, Permission::ManagePkbCatalog, true],
        'admin sistem dapat kelola katalog PKB' => [$as, Permission::ManagePkbCatalog, true],
        'supervisor TIDAK dapat kelola katalog PKB' => [$s, Permission::ManagePkbCatalog, false],
        'guru dapat merespons rekomendasi PKB' => [$g, Permission::RespondPkbRecommendation, true],
        'supervisor dapat menominasikan praktik baik' => [$s, Permission::NominateBestPractice, true],
        'guru dapat memberi persetujuan praktik baik' => [$g, Permission::RespondBestPracticeConsent, true],
        'admin dinas dapat mengkurasi praktik baik' => [$ad, Permission::CurateBestPractice, true],
        'supervisor TIDAK dapat mengkurasi praktik baik' => [$s, Permission::CurateBestPractice, false],
        'guru dapat mengirim penilaian 360' => [$g, Permission::SubmitSupervisorEvaluation, true],
        'supervisor TIDAK dapat mengirim penilaian 360' => [$s, Permission::SubmitSupervisorEvaluation, false],
        'supervisor dapat melihat laporan akuntabilitas' => [$s, Permission::ViewAccountabilityReport, true],
        'admin dinas dapat melihat laporan akuntabilitas' => [$ad, Permission::ViewAccountabilityReport, true],
        'guru TIDAK dapat melihat laporan akuntabilitas' => [$g, Permission::ViewAccountabilityReport, false],
        'admin dinas dapat mengelola kalibrasi' => [$ad, Permission::ManageCalibration, true],
        'supervisor dapat ikut kalibrasi' => [$s, Permission::ParticipateCalibration, true],
        'guru TIDAK dapat ikut kalibrasi' => [$g, Permission::ParticipateCalibration, false],

        // Admin Sistem
        'admin sistem dapat kelola pengguna' => [$as, Permission::ManageUsers, true],
        'admin sistem dapat kelola organisasi' => [$as, Permission::ManageOrganization, true],
        'admin sistem dapat kelola kebijakan' => [$as, Permission::ManagePolicySettings, true],
        'admin sistem dapat lihat audit' => [$as, Permission::ViewAuditLog, true],
        'admin sistem TIDAK dapat observasi (bukan konten pedagogis)' => [$as, Permission::ConductObservation, false],
        'admin sistem TIDAK dapat finalisasi analisis' => [$as, Permission::FinalizeAnalysis, false],
        'admin sistem TIDAK dapat lihat laporan agregat' => [$as, Permission::ViewAggregateReport, false],
    ];
});

it('matches the RBAC matrix', function (Role $role, Permission $permission, bool $expected) {
    expect(RolePermissionMap::grants($role, $permission))->toBe($expected);
})->with('rbac matrix');

it('gives every role the ability to manage its own profile', function () {
    foreach (Role::cases() as $role) {
        expect(RolePermissionMap::grants($role, Permission::ManageOwnProfile))->toBeTrue();
    }
});

it('never lets the AI request-draft permission belong to guru', function () {
    expect(RolePermissionMap::grants(Role::Guru, Permission::RequestAiDraft))->toBeFalse();
});

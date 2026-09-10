<?php

declare(strict_types=1);

namespace App\Domain\ProfessionalDev\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\ProfessionalDev\Notifications\BestPracticeStatusNotification;
use App\Models\BestPractice;
use App\Models\User;
use App\Support\Enums\Permission;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Admin Dinas mengkurasi entri praktik baik yang telah disetujui guru:
 * menerbitkan atau menolak (M10, @provisional).
 */
class CurateBestPractice
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function handle(User $admin, BestPractice $bestPractice, bool $terbit, ?string $catatan = null): BestPractice
    {
        if (! $admin->can(Permission::CurateBestPractice->value) || $admin->adminDinasId() !== $bestPractice->dinas_id) {
            throw new AuthorizationException('Entri ini di luar lingkup dinas Anda.');
        }

        if ($bestPractice->status !== BestPractice::STATUS_MENUNGGU_KURASI) {
            throw new DomainException('Entri ini belum siap dikurasi.');
        }

        return DB::transaction(function () use ($admin, $bestPractice, $terbit, $catatan): BestPractice {
            $bestPractice->forceFill([
                'status' => $terbit ? BestPractice::STATUS_TERBIT : BestPractice::STATUS_DITOLAK,
                'curated_by' => $admin->getKey(),
                'curated_at' => now(),
                'catatan_kurasi' => $catatan,
                'terbit_at' => $terbit ? now() : null,
            ])->save();

            $this->audit->log(
                $terbit ? 'best_practice.published' : 'best_practice.rejected',
                $bestPractice,
                context: ['catatan' => $catatan],
                actor: $admin,
            );

            if ($terbit) {
                $recipients = User::query()
                    ->whereKey([$bestPractice->guru_id, $bestPractice->nominated_by])
                    ->get();
                Notification::send($recipients, new BestPracticeStatusNotification(
                    $bestPractice,
                    'Praktik baik diterbitkan',
                    "Entri \"{$bestPractice->judul}\" kini tersedia di Perpustakaan Praktik Baik.",
                    route('best-practices.index'),
                ));
            }

            return $bestPractice->refresh();
        });
    }
}

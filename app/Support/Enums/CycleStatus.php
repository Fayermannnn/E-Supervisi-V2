<?php

declare(strict_types=1);

namespace App\Support\Enums;

/**
 * Status siklus supervisi (Spec §5). Dipetakan 1:1 ke enam tahap operasional
 * Spec §2.2 + status meta (draf, diarsipkan, dibatalkan).
 *
 * Nilai integer dijaga stabil — perubahan berdampak pada state machine & audit
 * historis (docs/state-machine.md).
 */
enum CycleStatus: int
{
    case Draft = 0;
    case Scheduled = 1;
    case ObservationDone = 2;
    case AnalysisDone = 3;
    case FeedbackGiven = 4;
    case FollowUpActive = 5;
    case FollowUpOverdue = 6;
    case Reported = 7;
    case Archived = 8;
    case Canceled = 9;

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Scheduled => 'Terjadwal',
            self::ObservationDone => 'Observasi Selesai',
            self::AnalysisDone => 'Analisis Selesai',
            self::FeedbackGiven => 'Umpan Balik Diberikan',
            self::FollowUpActive => 'Tindak Lanjut Berjalan',
            self::FollowUpOverdue => 'Tindak Lanjut Terlambat',
            self::Reported => 'Dilaporkan',
            self::Archived => 'Diarsipkan',
            self::Canceled => 'Dibatalkan',
        };
    }

    /**
     * Tahap operasional Spec §2.2 (null untuk status meta).
     */
    public function stage(): ?string
    {
        return match ($this) {
            self::Scheduled => 'Perencanaan',
            self::ObservationDone => 'Observasi',
            self::AnalysisDone => 'Analisis',
            self::FeedbackGiven => 'Umpan Balik',
            self::FollowUpActive, self::FollowUpOverdue => 'Tindak Lanjut',
            self::Reported => 'Pelaporan',
            default => null,
        };
    }

    /**
     * Warna badge UI (lihat <x-ui.status-badge>).
     */
    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'draft',
            self::Scheduled => 'scheduled',
            self::ObservationDone, self::AnalysisDone, self::FeedbackGiven => 'progress',
            self::FollowUpActive => 'progress',
            self::FollowUpOverdue => 'overdue',
            self::Reported => 'done',
            self::Archived => 'archived',
            self::Canceled => 'canceled',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Archived || $this === self::Canceled;
    }
}

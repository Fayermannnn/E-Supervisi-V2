<?php

declare(strict_types=1);

namespace App\Domain\Accountability\Actions;

use App\Domain\Audit\AuditLogger;
use App\Models\CalibrationSession;
use App\Models\User;
use App\Support\Enums\Permission;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Seorang penilai mengirim skor item independennya untuk sesi kalibrasi (M12).
 */
class SubmitCalibrationScores
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, float|int>  $scores  item_key => nilai
     *
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function handle(User $supervisor, CalibrationSession $session, array $scores): void
    {
        if (! $supervisor->can(Permission::ParticipateCalibration->value)) {
            throw new AuthorizationException('Anda bukan penilai kalibrasi.');
        }

        $participant = $session->participants()->where('supervisor_id', $supervisor->getKey())->first();
        if ($participant === null) {
            throw new AuthorizationException('Anda tidak terdaftar sebagai penilai pada sesi ini.');
        }

        if ($session->status !== CalibrationSession::STATUS_BERJALAN) {
            throw new DomainException('Sesi kalibrasi tidak sedang berjalan.');
        }

        $schema = $session->instrumentVersion()->sole()->schema();

        /** @var array<string, string> $sectionByItem */
        $sectionByItem = [];
        foreach ($schema->sections as $section) {
            foreach ($section->items as $item) {
                if ($item->type->isScorable()) {
                    $sectionByItem[$item->key] = $section->key;
                }
            }
        }

        $clean = [];
        foreach ($scores as $itemKey => $nilai) {
            if (! array_key_exists($itemKey, $sectionByItem)) {
                throw new DomainException("Item tidak dikenal pada instrumen: {$itemKey}.");
            }
            $clean[$itemKey] = (float) $nilai;
        }

        if ($clean === []) {
            throw new DomainException('Tidak ada skor yang dikirim.');
        }

        DB::transaction(function () use ($session, $participant, $clean, $sectionByItem, $supervisor): void {
            foreach ($clean as $itemKey => $nilai) {
                $participant->scores()->updateOrCreate(
                    ['item_key' => $itemKey],
                    [
                        'calibration_session_id' => $session->getKey(),
                        'section_key' => $sectionByItem[$itemKey],
                        'nilai' => $nilai,
                    ],
                );
            }

            $participant->forceFill(['submitted_at' => now()])->save();
            $this->audit->log('calibration.scores_submitted', $session, new: ['item' => count($clean)], actor: $supervisor);
        });
    }
}

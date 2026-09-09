<?php

declare(strict_types=1);

namespace App\Domain\Observation\Actions;

use App\Domain\Instruments\Enums\ItemType;
use App\Domain\Observation\Exceptions\ObservationConflictException;
use App\Models\Observation;
use App\Models\ObservationResponse;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Menyimpan (autosave / sinkron) isi observasi. Idempoten pada level item
 * (unik observation_id+item_key). Optimistic lock via `version`.
 */
class SaveObservation
{
    /**
     * @param  list<array<string, mixed>>  $responses  tiap entri: item_key, section_key, value?, catatan_item?
     * @param  array<string, mixed>  $meta  catatan_skrip?, mulai_at?, selesai_at?, client_updated_at?
     *
     * @throws ObservationConflictException bila base version klien tertinggal
     * @throws DomainException bila observasi sudah final
     */
    public function handle(
        Observation $observation,
        array $responses,
        array $meta = [],
        ?int $expectedVersion = null,
    ): Observation {
        if ($observation->isFinal()) {
            throw new DomainException('Observasi sudah final dan tidak dapat diubah.');
        }

        if ($expectedVersion !== null && $expectedVersion !== $observation->version) {
            throw new ObservationConflictException($observation, $expectedVersion);
        }

        $schema = $observation->instrumentVersion()->sole()->schema();

        return DB::transaction(function () use ($observation, $responses, $meta, $schema): Observation {
            foreach ($responses as $row) {
                $itemKey = (string) ($row['item_key'] ?? '');
                $item = $schema->item($itemKey);
                if ($item === null) {
                    continue; // abaikan item yang bukan bagian instrumen
                }

                ObservationResponse::updateOrCreate(
                    ['observation_id' => $observation->getKey(), 'item_key' => $itemKey],
                    array_merge(
                        [
                            'instrument_version_id' => $observation->instrument_version_id,
                            'section_key' => (string) ($row['section_key'] ?? ''),
                            'value_numeric' => null,
                            'value_boolean' => null,
                            'value_text' => null,
                            'value_json' => null,
                            'catatan_item' => isset($row['catatan_item']) ? (string) $row['catatan_item'] : null,
                        ],
                        $this->columnFor($item->type, $row['value'] ?? null),
                    ),
                );
            }

            $observation->fill(array_filter([
                'catatan_skrip' => $meta['catatan_skrip'] ?? $observation->catatan_skrip,
                'mulai_at' => $meta['mulai_at'] ?? $observation->mulai_at,
                'selesai_at' => $meta['selesai_at'] ?? $observation->selesai_at,
                'client_updated_at' => $meta['client_updated_at'] ?? now(),
            ], static fn ($v) => $v !== null));

            $observation->version = $observation->version + 1;
            $observation->save();

            return $observation->refresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function columnFor(ItemType $type, mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        return match ($type) {
            ItemType::Likert, ItemType::Numeric => ['value_numeric' => (float) $value],
            ItemType::Boolean => ['value_boolean' => (bool) $value],
            ItemType::Text => ['value_text' => (string) $value],
            ItemType::Checklist => ['value_json' => array_values((array) $value)],
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Instruments;

use App\Domain\Instruments\Enums\ItemType;

final class SchemaItem
{
    /**
     * @param  list<string>  $options
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly ItemType $type,
        public readonly bool $required,
        public readonly ?float $scaleMin,
        public readonly ?float $scaleMax,
        public readonly float $weight,
        public readonly array $options,
    ) {}

    /**
     * Normalisasi nilai jawaban mentah ke skor 0..1 (untuk agregasi Fase 3).
     * Mengembalikan null bila item tidak menyumbang skor.
     */
    public function normalizedScore(mixed $value): ?float
    {
        if (! $this->type->isScorable() || $value === null || $value === '') {
            return null;
        }

        return match ($this->type) {
            ItemType::Boolean => ((bool) $value) ? 1.0 : 0.0,
            ItemType::Likert, ItemType::Numeric => $this->scaleNormalize((float) $value),
            default => null,
        };
    }

    private function scaleNormalize(float $value): ?float
    {
        $min = $this->scaleMin ?? 1.0;
        $max = $this->scaleMax ?? 4.0;

        if ($max <= $min) {
            return null;
        }

        return max(0.0, min(1.0, ($value - $min) / ($max - $min)));
    }
}

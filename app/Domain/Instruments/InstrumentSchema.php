<?php

declare(strict_types=1);

namespace App\Domain\Instruments;

use App\Domain\Instruments\Enums\ItemType;

/**
 * Value object atas `instrument_versions.schema_json` (ADR-013).
 *
 * Bentuk:
 * {
 *   "sections": [
 *     { "key": "pendahuluan", "title": "Kegiatan Pendahuluan", "items": [
 *       { "key": "apersepsi", "label": "Melakukan apersepsi", "type": "likert",
 *         "scale": { "min": 1, "max": 4 }, "weight": 1, "required": true },
 *       { "key": "catatan", "label": "Catatan", "type": "text", "required": false }
 *     ]}
 *   ]
 * }
 */
final class InstrumentSchema
{
    /**
     * @param  list<SchemaSection>  $sections
     */
    private function __construct(public readonly array $sections) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $sections = [];

        foreach ($data['sections'] ?? [] as $rawSection) {
            if (! is_array($rawSection)) {
                continue;
            }

            $items = [];
            foreach ($rawSection['items'] ?? [] as $rawItem) {
                if (! is_array($rawItem)) {
                    continue;
                }

                $type = ItemType::tryFrom((string) ($rawItem['type'] ?? 'text')) ?? ItemType::Text;

                $items[] = new SchemaItem(
                    key: (string) ($rawItem['key'] ?? ''),
                    label: (string) ($rawItem['label'] ?? ''),
                    type: $type,
                    required: (bool) ($rawItem['required'] ?? false),
                    scaleMin: isset($rawItem['scale']['min']) ? (float) $rawItem['scale']['min'] : null,
                    scaleMax: isset($rawItem['scale']['max']) ? (float) $rawItem['scale']['max'] : null,
                    weight: (float) ($rawItem['weight'] ?? 1),
                    options: array_values(array_map('strval', (array) ($rawItem['options'] ?? []))),
                );
            }

            $sections[] = new SchemaSection(
                key: (string) ($rawSection['key'] ?? ''),
                title: (string) ($rawSection['title'] ?? ''),
                items: $items,
            );
        }

        return new self($sections);
    }

    /**
     * @return list<SchemaItem>
     */
    public function items(): array
    {
        return array_merge(...array_map(static fn (SchemaSection $s): array => $s->items, $this->sections)) ?: [];
    }

    public function item(string $key): ?SchemaItem
    {
        foreach ($this->items() as $item) {
            if ($item->key === $key) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function requiredItemKeys(): array
    {
        return array_values(array_map(
            static fn (SchemaItem $i): string => $i->key,
            array_filter($this->items(), static fn (SchemaItem $i): bool => $i->required),
        ));
    }

    /**
     * Validasi struktur mentah. Mengembalikan daftar pesan kesalahan (kosong = valid).
     *
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    public static function validate(array $data): array
    {
        $errors = [];
        $sections = $data['sections'] ?? null;

        if (! is_array($sections) || $sections === []) {
            return ['Skema wajib memiliki minimal satu seksi.'];
        }

        $seenItemKeys = [];

        foreach ($sections as $i => $section) {
            if (! is_array($section) || empty($section['key']) || empty($section['title'])) {
                $errors[] = 'Seksi #'.($i + 1)." wajib punya 'key' dan 'title'.";

                continue;
            }

            $items = $section['items'] ?? [];
            if (! is_array($items) || $items === []) {
                $errors[] = "Seksi '{$section['key']}' wajib punya minimal satu item.";

                continue;
            }

            foreach ($items as $j => $item) {
                if (! is_array($item) || empty($item['key']) || empty($item['label'])) {
                    $errors[] = 'Item #'.($j + 1)." pada seksi '{$section['key']}' wajib punya 'key' dan 'label'.";

                    continue;
                }

                if (ItemType::tryFrom((string) ($item['type'] ?? '')) === null) {
                    $errors[] = "Item '{$item['key']}' memiliki tipe tidak dikenal.";
                }

                if (in_array($item['key'], $seenItemKeys, true)) {
                    $errors[] = "Kunci item '{$item['key']}' duplikat — harus unik di seluruh instrumen.";
                }
                $seenItemKeys[] = $item['key'];
            }
        }

        return $errors;
    }
}

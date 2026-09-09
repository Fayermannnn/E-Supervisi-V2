<?php

declare(strict_types=1);

use App\Domain\Instruments\Enums\ItemType;
use App\Domain\Instruments\FormatBTemplate;
use App\Domain\Instruments\InstrumentSchema;

it('parses the Format B template into sections and items', function () {
    $schema = InstrumentSchema::fromArray(FormatBTemplate::schema());

    expect($schema->sections)->toHaveCount(3)
        ->and($schema->item('apersepsi')?->type)->toBe(ItemType::Likert)
        ->and($schema->item('catatan_umum')?->required)->toBeFalse();
});

it('lists required item keys', function () {
    $schema = InstrumentSchema::fromArray(FormatBTemplate::schema());

    expect($schema->requiredItemKeys())->toContain('apersepsi', 'penguasaan_materi', 'tindak_lanjut')
        ->not->toContain('catatan_umum');
});

it('validates a well-formed schema as having no errors', function () {
    expect(InstrumentSchema::validate(FormatBTemplate::schema()))->toBe([]);
});

it('reports errors for malformed schemas', function () {
    expect(InstrumentSchema::validate(['sections' => []]))->not->toBe([]);
    expect(InstrumentSchema::validate([
        'sections' => [
            ['key' => 's1', 'title' => 'S1', 'items' => [
                ['key' => 'dup', 'label' => 'A', 'type' => 'likert'],
                ['key' => 'dup', 'label' => 'B', 'type' => 'text'],
            ]],
        ],
    ]))->toContain("Kunci item 'dup' duplikat — harus unik di seluruh instrumen.");
});

it('normalizes likert values to a 0..1 score', function () {
    $schema = InstrumentSchema::fromArray(FormatBTemplate::schema());
    $item = $schema->item('apersepsi');

    expect($item?->normalizedScore(1))->toBe(0.0)
        ->and($item?->normalizedScore(4))->toBe(1.0)
        ->and($item?->normalizedScore(null))->toBeNull();
});

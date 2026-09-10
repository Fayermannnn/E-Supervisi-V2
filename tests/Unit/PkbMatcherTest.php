<?php

declare(strict_types=1);

use App\Domain\ProfessionalDev\PkbMatcher;

it('extracts meaningful keywords and drops stopwords and short tokens', function () {
    $keywords = PkbMatcher::keywords('Guru perlu menguatkan pertanyaan pemantik pada kegiatan inti');

    expect($keywords)->toContain('pertanyaan')
        ->and($keywords)->toContain('pemantik')
        ->and($keywords)->toContain('menguatkan')
        ->and($keywords)->not->toContain('guru')   // stopword
        ->and($keywords)->not->toContain('perlu')   // stopword
        ->and($keywords)->not->toContain('pada');    // < 4 huruf
});

it('matches catalog items by tag keyword overlap only', function () {
    $keywords = PkbMatcher::keywords('Perlu penguatan pada pemberian pertanyaan pemantik dan asesmen formatif');

    $matches = PkbMatcher::match($keywords, [
        ['id' => 'item-a', 'tags' => ['pertanyaan pemantik', 'diskusi kelompok']],
        ['id' => 'item-b', 'tags' => ['asesmen formatif']],
        ['id' => 'item-c', 'tags' => ['manajemen sarana prasarana']],
    ]);

    expect($matches)->toHaveKeys(['item-a', 'item-b'])
        ->and($matches)->not->toHaveKey('item-c')
        ->and($matches['item-a'])->toContain('pertanyaan pemantik');
});

it('is order-stable and returns an empty array when nothing matches', function () {
    expect(PkbMatcher::match(['xyz', 'abc'], [['id' => 'x', 'tags' => ['tidak relevan']]]))->toBe([]);
});

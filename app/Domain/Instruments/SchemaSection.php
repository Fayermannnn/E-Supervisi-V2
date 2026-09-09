<?php

declare(strict_types=1);

namespace App\Domain\Instruments;

final class SchemaSection
{
    /**
     * @param  list<SchemaItem>  $items
     */
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly array $items,
    ) {}
}

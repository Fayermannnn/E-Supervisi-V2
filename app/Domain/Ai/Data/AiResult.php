<?php

declare(strict_types=1);

namespace App\Domain\Ai\Data;

final class AiResult
{
    /**
     * @param  array<string, mixed>|null  $tokenUsage
     */
    public function __construct(
        public readonly string $output,
        public readonly string $provider,
        public readonly ?string $model = null,
        public readonly ?array $tokenUsage = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Domain\Ai\Data;

/**
 * Permintaan ke penyedia AI. `renderedPrompt` sudah menyertakan konteks;
 * `context` disimpan terpisah untuk audit (ai_generations.input_context).
 */
final class AiRequest
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly string $promptKey,
        public readonly int $promptVersion,
        public readonly string $renderedPrompt,
        public readonly array $context = [],
        public readonly ?string $model = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Domain\Ai\Contracts;

use App\Domain\Ai\Data\AiRequest;
use App\Domain\Ai\Data\AiResult;

/**
 * Abstraksi penyedia AI (ADR-009, Spec §16). Aplikasi tidak boleh terkunci
 * pada satu penyedia. Implementasi tidak punya akses database.
 */
interface AiProvider
{
    public function name(): string;

    public function generate(AiRequest $request): AiResult;
}

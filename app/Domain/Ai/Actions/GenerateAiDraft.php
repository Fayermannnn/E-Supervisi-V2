<?php

declare(strict_types=1);

namespace App\Domain\Ai\Actions;

use App\Domain\Ai\Jobs\RunAiGeneration;
use App\Domain\Audit\AuditLogger;
use App\Models\AiGeneration;
use App\Models\AiPromptTemplate;
use App\Models\User;
use App\Support\Enums\Permission;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

/**
 * Meminta draf AI untuk sebuah entitas sumber (mis. analysis_result).
 * Selalu asinkron (Job); baris ai_generations dibuat berstatus `pending`.
 */
class GenerateAiDraft
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $context
     *
     * @throws AuthorizationException
     * @throws RuntimeException bila rate limit terlampaui atau template tak ada
     */
    public function handle(
        User $requester,
        string $sourceType,
        string $sourceId,
        string $promptKey,
        array $context,
    ): AiGeneration {
        if (! $requester->can(Permission::RequestAiDraft->value)) {
            throw new AuthorizationException('Anda tidak berwenang meminta draf AI.');
        }

        $key = 'ai-draft:'.$requester->getKey();
        if (RateLimiter::tooManyAttempts($key, (int) config('ai.rate_limit_per_minute', 6))) {
            throw new RuntimeException('Terlalu banyak permintaan draf AI. Coba lagi sebentar.');
        }
        RateLimiter::hit($key, 60);

        $template = AiPromptTemplate::active($promptKey);
        if ($template === null) {
            throw new RuntimeException("Template prompt '{$promptKey}' tidak tersedia.");
        }

        $generation = AiGeneration::create([
            'provider' => (string) config('ai.provider', 'mock'),
            'prompt_key' => $promptKey,
            'prompt_version' => $template->version,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'input_context' => $context,
            'status' => AiGeneration::STATUS_PENDING,
            'review_status' => AiGeneration::REVIEW_DRAFT,
        ]);

        $this->audit->log('ai.draft_requested', $generation, context: [
            'prompt_key' => $promptKey,
            'source' => $sourceType.':'.$sourceId,
        ], actor: $requester);

        RunAiGeneration::dispatch($generation->id);

        return $generation;
    }
}

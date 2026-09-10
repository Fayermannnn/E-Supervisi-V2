<?php

declare(strict_types=1);

namespace App\Domain\Ai\Jobs;

use App\Domain\Ai\Contracts\AiProvider;
use App\Domain\Ai\Data\AiRequest;
use App\Domain\Ai\PromptRenderer;
use App\Models\AiGeneration;
use App\Models\AiPromptTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RunAiGeneration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public readonly string $generationId) {}

    public function handle(AiProvider $provider, PromptRenderer $renderer): void
    {
        $generation = AiGeneration::query()->find($this->generationId);
        if ($generation === null || $generation->status !== AiGeneration::STATUS_PENDING) {
            return;
        }

        $template = AiPromptTemplate::query()
            ->where('key', $generation->prompt_key)
            ->where('version', $generation->prompt_version)
            ->first();

        if ($template === null) {
            $this->markFailed($generation, 'Template prompt hilang.');

            return;
        }

        try {
            $result = $provider->generate(new AiRequest(
                promptKey: $generation->prompt_key,
                promptVersion: $generation->prompt_version,
                renderedPrompt: $renderer->render($template->template, $generation->input_context ?? []),
                context: $generation->input_context ?? [],
            ));

            $generation->forceFill([
                'provider' => $result->provider,
                'model' => $result->model,
                'output' => $result->output,
                'token_usage' => $result->tokenUsage,
                'status' => AiGeneration::STATUS_DRAFT,
                'review_status' => AiGeneration::REVIEW_DRAFT,
                'generated_at' => now(),
                'error' => null,
            ])->save();
        } catch (Throwable $e) {
            $this->markFailed($generation, $e->getMessage());
        }
    }

    private function markFailed(AiGeneration $generation, string $message): void
    {
        $generation->forceFill([
            'status' => AiGeneration::STATUS_FAILED,
            'error' => $message,
            'generated_at' => now(),
        ])->save();
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Ai\Providers;

use App\Domain\Ai\Contracts\AiProvider;
use App\Domain\Ai\Data\AiRequest;
use App\Domain\Ai\Data\AiResult;
use Illuminate\Support\Facades\Http;

/**
 * Penyedia OpenAI (Chat Completions). Diaktifkan via AI_PROVIDER=openai +
 * OPENAI_API_KEY. Tanpa kunci, container jatuh ke MockAiProvider (AiServiceProvider).
 */
class OpenAiProvider implements AiProvider
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config) {}

    public function name(): string
    {
        return 'openai';
    }

    public function generate(AiRequest $request): AiResult
    {
        $model = $request->model ?? ($this->config['model'] ?? 'gpt-4o-mini');

        $response = Http::withToken((string) $this->config['api_key'])
            ->timeout((int) config('ai.timeout', 30))
            ->retry((int) config('ai.max_attempts', 2), 500)
            ->post(rtrim((string) $this->config['base_url'], '/').'/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Anda asisten supervisi klinis pendidikan. Jawab ringkas, dalam Bahasa Indonesia, sebagai DRAF untuk ditinjau supervisor.'],
                    ['role' => 'user', 'content' => $request->renderedPrompt],
                ],
                'temperature' => 0.3,
            ])
            ->throw()
            ->json();

        return new AiResult(
            output: (string) ($response['choices'][0]['message']['content'] ?? ''),
            provider: 'openai',
            model: $model,
            tokenUsage: $response['usage'] ?? null,
        );
    }
}

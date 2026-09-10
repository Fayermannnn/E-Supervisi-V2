<?php

declare(strict_types=1);

namespace App\Domain\Ai\Providers;

use App\Domain\Ai\Contracts\AiProvider;
use App\Domain\Ai\Data\AiRequest;
use App\Domain\Ai\Data\AiResult;
use Illuminate\Support\Facades\Http;

/**
 * Penyedia Anthropic (Messages API). Diaktifkan via AI_PROVIDER=anthropic +
 * ANTHROPIC_API_KEY.
 */
class AnthropicProvider implements AiProvider
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config) {}

    public function name(): string
    {
        return 'anthropic';
    }

    public function generate(AiRequest $request): AiResult
    {
        $model = $request->model ?? ($this->config['model'] ?? 'claude-sonnet-5');

        $response = Http::withHeaders([
            'x-api-key' => (string) $this->config['api_key'],
            'anthropic-version' => '2023-06-01',
        ])
            ->timeout((int) config('ai.timeout', 30))
            ->retry((int) config('ai.max_attempts', 2), 500)
            ->post(rtrim((string) $this->config['base_url'], '/').'/v1/messages', [
                'model' => $model,
                'max_tokens' => 1024,
                'system' => 'Anda asisten supervisi klinis pendidikan. Jawab ringkas, dalam Bahasa Indonesia, sebagai DRAF untuk ditinjau supervisor.',
                'messages' => [['role' => 'user', 'content' => $request->renderedPrompt]],
            ])
            ->throw()
            ->json();

        $text = '';
        foreach ($response['content'] ?? [] as $block) {
            if (($block['type'] ?? null) === 'text') {
                $text .= $block['text'];
            }
        }

        return new AiResult(
            output: $text,
            provider: 'anthropic',
            model: $model,
            tokenUsage: $response['usage'] ?? null,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Ai;

use App\Domain\Ai\Contracts\AiProvider;
use App\Domain\Ai\Providers\AnthropicProvider;
use App\Domain\Ai\Providers\MockAiProvider;
use App\Domain\Ai\Providers\OpenAiProvider;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiProvider::class, function (): AiProvider {
            $name = (string) config('ai.provider', 'mock');
            $config = (array) config("ai.providers.{$name}", []);

            // Tanpa kunci API -> paksa mock (ADR-009).
            $provider = match ($name) {
                'openai' => empty($config['api_key']) ? null : new OpenAiProvider($config),
                'anthropic' => empty($config['api_key']) ? null : new AnthropicProvider($config),
                default => null,
            };

            return $provider ?? new MockAiProvider;
        });
    }
}

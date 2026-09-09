<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Provider AI aktif
    |--------------------------------------------------------------------------
    |
    | Menentukan implementasi App\Domain\Ai\Contracts\AiProvider yang dipakai.
    | Nilai: "mock" (default, deterministik, tanpa kunci API), "openai",
    | "anthropic". Bila provider nyata dipilih tanpa kunci API, container
    | wajib jatuh kembali ke "mock" (lihat AiServiceProvider — Fase 3).
    |
    | Prinsip: seluruh keluaran AI berstatus DRAFT dan tidak pernah menjadi
    | data final tanpa tinjauan manusia (Spec §6.6, §10, §11; RULE 4).
    |
    */

    'provider' => env('AI_PROVIDER', 'mock'),

    'providers' => [

        'mock' => [
            'driver' => 'mock',
        ],

        'openai' => [
            'driver' => 'openai',
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        ],

        'anthropic' => [
            'driver' => 'anthropic',
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-5'),
            'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Batas & keamanan
    |--------------------------------------------------------------------------
    */

    'timeout' => (int) env('AI_TIMEOUT', 30),
    'max_attempts' => (int) env('AI_MAX_ATTEMPTS', 2),

    // Rate limit panggilan AI per pengguna per menit.
    'rate_limit_per_minute' => (int) env('AI_RATE_LIMIT', 6),

];

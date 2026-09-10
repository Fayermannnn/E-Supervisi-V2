<?php

declare(strict_types=1);

use App\Domain\Ai\Actions\GenerateAiDraft;
use App\Domain\Ai\Actions\ReviewAiGeneration;
use App\Domain\Ai\Contracts\AiProvider;
use App\Domain\Ai\Providers\MockAiProvider;
use App\Models\AiGeneration;
use App\Models\AnalysisResult;
use App\Models\User;
use App\Support\Enums\Role;

beforeEach(function () {
    $this->seed(Database\Seeders\AiPromptTemplateSeeder::class);
    $this->supervisor = User::factory()->create();
    $this->supervisor->assignRole(Role::Supervisor);
});

it('defaults to the deterministic mock provider', function () {
    expect(app(AiProvider::class))->toBeInstanceOf(MockAiProvider::class);
});

it('produces a draft that is never auto-approved', function () {
    $gen = app(GenerateAiDraft::class)->handle(
        $this->supervisor, AnalysisResult::class, Illuminate\Support\Str::uuid7()->toString(),
        'analysis_summary', ['score_summary' => ['total' => 0.75, 'band' => 'Baik'], 'strengths' => ['A'], 'growth_areas' => ['B']],
    );

    // Job runs sync in tests
    $gen->refresh();
    expect($gen->status)->toBe(AiGeneration::STATUS_DRAFT)
        ->and($gen->review_status)->toBe(AiGeneration::REVIEW_DRAFT)
        ->and($gen->isHumanApproved())->toBeFalse()
        ->and($gen->usableText())->toBeNull()
        ->and($gen->output)->toContain('MockAiProvider');
});

it('requires the request-draft permission', function () {
    $guru = User::factory()->create();
    $guru->assignRole(Role::Guru);

    expect(fn () => app(GenerateAiDraft::class)->handle($guru, AnalysisResult::class, 'x', 'analysis_summary', []))
        ->toThrow(Illuminate\Auth\Access\AuthorizationException::class);
});

it('only becomes usable text after a human accepts or edits it', function () {
    $gen = app(GenerateAiDraft::class)->handle(
        $this->supervisor, AnalysisResult::class, Illuminate\Support\Str::uuid7()->toString(),
        'analysis_summary', [],
    );
    $gen->refresh();

    app(ReviewAiGeneration::class)->handle($this->supervisor, $gen, 'accept');
    expect($gen->refresh()->isHumanApproved())->toBeTrue()
        ->and($gen->usableText())->not->toBeNull();

    $edited = app(GenerateAiDraft::class)->handle($this->supervisor, AnalysisResult::class, Illuminate\Support\Str::uuid7()->toString(), 'analysis_summary', []);
    app(ReviewAiGeneration::class)->handle($this->supervisor, $edited->refresh(), 'edit', 'Teks yang disunting manusia.');
    expect($edited->refresh()->usableText())->toBe('Teks yang disunting manusia.');
});

it('rate-limits draft requests per user', function () {
    for ($i = 0; $i < 6; $i++) {
        app(GenerateAiDraft::class)->handle($this->supervisor, AnalysisResult::class, Illuminate\Support\Str::uuid7()->toString(), 'analysis_summary', []);
    }

    expect(fn () => app(GenerateAiDraft::class)->handle($this->supervisor, AnalysisResult::class, 'x', 'analysis_summary', []))
        ->toThrow(RuntimeException::class);
});

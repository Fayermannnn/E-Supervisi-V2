<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Asisten AI (M18, @provisional). Setiap keluaran berstatus DRAFT sampai
 * ditinjau manusia (Spec §6.6, §10, §11; RULE 4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_prompt_templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('key'); // analysis_summary | feedback_suggestion | ...
            $table->unsignedInteger('version');
            $table->longText('template');
            $table->jsonb('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['key', 'version']);
        });

        Schema::create('ai_generations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('provider');
            $table->string('model')->nullable();
            $table->string('prompt_key');
            $table->unsignedInteger('prompt_version');
            $table->string('source_type');
            $table->uuid('source_id');
            $table->jsonb('input_context')->nullable();
            $table->longText('output')->nullable();
            $table->string('status')->default('pending'); // pending | draft | failed
            $table->string('review_status')->default('draft'); // draft | accepted | edited | rejected
            $table->longText('reviewed_output')->nullable();
            $table->foreignUuid('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->jsonb('token_usage')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['prompt_key', 'review_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
        Schema::dropIfExists('ai_prompt_templates');
    }
};

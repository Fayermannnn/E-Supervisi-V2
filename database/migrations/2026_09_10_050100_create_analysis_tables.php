<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Analisis hasil observasi (M3, @provisional — struktur dapat berubah additive
 * setelah SLR Gate 6/7).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_results', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cycle_id')->constrained('supervision_cycles')->cascadeOnDelete();
            $table->foreignUuid('observation_id')->nullable()->constrained('observations')->nullOnDelete();
            $table->jsonb('score_summary')->nullable(); // per-seksi + total (hasil scoring_config)
            $table->longText('ringkasan')->nullable();
            $table->string('sumber')->default('manual'); // manual | ai_draft | ai_edited
            $table->string('status_review')->default('draft'); // draft | in_review | final
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('ai_generation_id')->nullable()->constrained('ai_generations')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->unique('cycle_id');
        });

        Schema::create('analysis_findings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('analysis_result_id')->constrained('analysis_results')->cascadeOnDelete();
            $table->string('kategori'); // kekuatan | area_pengembangan | pola
            $table->text('deskripsi');
            $table->jsonb('bukti_ref')->nullable(); // tautan ke item/response
            $table->unsignedTinyInteger('prioritas')->default(3);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_findings');
        Schema::dropIfExists('analysis_results');
    }
};

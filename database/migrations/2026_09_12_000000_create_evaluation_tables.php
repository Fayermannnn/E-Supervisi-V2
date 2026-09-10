<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Evaluasi ahli atas artefak sistem — mendukung DSR Artikel 3 Fase 5
 * (Spec §12 Fase 5, §14). Bukan bagian siklus supervisi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_panels', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('artefak_versi'); // mis. "MVP Fase 1–4 (tag phase4-complete)"
            $table->string('status')->default('draft'); // draft | berjalan | selesai
            $table->foreignUuid('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->jsonb('stats')->nullable(); // snapshot CVR/CVI/Aiken's V/SUS saat panel ditutup
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('panel_experts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('evaluation_panel_id')->constrained('evaluation_panels')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->string('rumpun'); // manajemen_pendidikan | sistem_informasi | lainnya
            $table->text('afiliasi')->nullable();
            $table->timestamp('diundang_at')->nullable();
            $table->timestamps();

            $table->unique(['evaluation_panel_id', 'user_id']);
        });

        Schema::create('expert_reviews', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('panel_expert_id')->constrained('panel_experts')->cascadeOnDelete();
            $table->string('status')->default('draft'); // draft | terkirim
            $table->jsonb('jawaban'); // { relevansi: {aspek: esensial|berguna|tidak_perlu}, kualitas: {aspek: 1..5}, sus: {s1..s10: 1..5} }
            $table->text('catatan')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique('panel_expert_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_reviews');
        Schema::dropIfExists('panel_experts');
        Schema::dropIfExists('evaluation_panels');
    }
};

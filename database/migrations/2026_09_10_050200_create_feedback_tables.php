<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pemberian umpan balik (M4, @provisional).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cycle_id')->constrained('supervision_cycles')->cascadeOnDelete();
            $table->timestamp('dijadwalkan_at')->nullable();
            $table->timestamp('dilaksanakan_at')->nullable();
            $table->string('metode')->default('tatap_muka'); // tatap_muka | daring
            $table->string('status')->default('draft'); // draft | berlangsung | selesai
            $table->string('status_konfirmasi_guru')->default('menunggu'); // menunggu | dikonfirmasi
            $table->timestamp('dikonfirmasi_guru_at')->nullable();
            $table->timestamps();

            $table->unique('cycle_id');
        });

        Schema::create('feedback_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('feedback_session_id')->constrained('feedback_sessions')->cascadeOnDelete();
            $table->foreignUuid('pengirim_id')->constrained('users')->restrictOnDelete();
            $table->string('peran'); // supervisor | guru
            $table->string('tipe'); // observasi | pertanyaan_reflektif | tanggapan | kesepakatan
            $table->longText('konten');
            $table->unsignedInteger('urutan')->default(0);
            $table->foreignUuid('ai_generation_id')->nullable()->constrained('ai_generations')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index('feedback_session_id');
        });

        Schema::create('feedback_agreements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('feedback_session_id')->constrained('feedback_sessions')->cascadeOnDelete();
            $table->text('poin_kesepakatan');
            $table->boolean('disepakati_kedua_pihak')->default(false);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_agreements');
        Schema::dropIfExists('feedback_messages');
        Schema::dropIfExists('feedback_sessions');
    }
};

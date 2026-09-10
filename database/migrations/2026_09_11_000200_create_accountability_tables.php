<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Akuntabilitas & Kualitas Supervisor (M11 Akuntabilitas 360° + M12 Kalibrasi
 * Antar-Penilai). @provisional. Guru menilai PROSES supervisi, bukan sebaliknya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisor_evaluations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cycle_id')->constrained('supervision_cycles')->cascadeOnDelete();
            $table->foreignUuid('guru_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('supervisor_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('dinas_id')->constrained('dinas')->restrictOnDelete();
            $table->foreignUuid('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->jsonb('jawaban'); // { dimensi_key: 1..4 }
            $table->text('komentar')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->unique('cycle_id'); // satu guru per siklus → satu penilaian
            $table->index('supervisor_id');
            $table->index('dinas_id');
        });

        Schema::create('calibration_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('dinas_id')->constrained('dinas')->restrictOnDelete();
            $table->foreignUuid('instrument_version_id')->constrained('instrument_versions')->restrictOnDelete();
            $table->foreignUuid('observation_id')->nullable()->constrained('observations')->nullOnDelete();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('artefak_url')->nullable(); // tautan rekaman/artefak yang dinilai bersama
            $table->string('status')->default('draft'); // draft | berjalan | selesai
            $table->foreignUuid('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->jsonb('stats')->nullable(); // snapshot statistik reliabilitas saat sesi ditutup
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['dinas_id', 'status']);
        });

        Schema::create('calibration_participants', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('calibration_session_id')->constrained('calibration_sessions')->cascadeOnDelete();
            $table->foreignUuid('supervisor_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['calibration_session_id', 'supervisor_id']);
        });

        Schema::create('calibration_scores', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('calibration_session_id')->constrained('calibration_sessions')->cascadeOnDelete();
            $table->foreignUuid('calibration_participant_id')->constrained('calibration_participants')->cascadeOnDelete();
            $table->string('section_key')->nullable();
            $table->string('item_key');
            $table->decimal('nilai', 6, 3);
            $table->timestamps();

            $table->unique(['calibration_participant_id', 'item_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calibration_scores');
        Schema::dropIfExists('calibration_participants');
        Schema::dropIfExists('calibration_sessions');
        Schema::dropIfExists('supervisor_evaluations');
    }
};

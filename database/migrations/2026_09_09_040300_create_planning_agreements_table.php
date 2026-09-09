<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kesepakatan pra-observasi (M1, Spec §7). Satu per siklus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planning_agreements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cycle_id')->unique()->constrained('supervision_cycles')->cascadeOnDelete();
            $table->text('fokus_observasi');
            $table->text('tujuan')->nullable();
            $table->foreignUuid('instrument_id')->constrained('instruments')->restrictOnDelete();
            $table->foreignUuid('instrument_version_id')->constrained('instrument_versions')->restrictOnDelete();
            $table->string('tipe_observasi'); // sinkron | asinkron
            $table->timestamp('jadwal_mulai');
            $table->timestamp('jadwal_selesai')->nullable();
            $table->string('lokasi')->nullable();
            $table->string('kelas')->nullable();
            $table->string('mata_pelajaran')->nullable();
            $table->timestamp('disepakati_guru_at')->nullable();
            $table->timestamp('disepakati_supervisor_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planning_agreements');
    }
};

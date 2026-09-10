<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manajemen Program Supervisi Tahunan (M7, Confirmed). Supervisor menyusun
 * program untuk guru binaannya per tahun ajaran/semester, lalu menyemai
 * siklus (status DRAFT) secara massal dari daftar target.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_programs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('dinas_id')->constrained('dinas')->restrictOnDelete();
            $table->string('tahun_ajaran');
            $table->string('semester'); // ganjil | genap
            $table->string('judul');
            $table->text('catatan')->nullable();
            $table->string('status')->default('draft'); // draft | aktif | selesai | dibatalkan
            $table->timestamps();

            $table->index(['owner_id', 'tahun_ajaran']);
            $table->index(['dinas_id', 'status']);
        });

        Schema::create('program_targets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('annual_program_id')->constrained('annual_programs')->cascadeOnDelete();
            $table->foreignUuid('guru_id')->constrained('users')->restrictOnDelete();
            $table->text('fokus_ringkas')->nullable();
            $table->date('rencana_mulai')->nullable();
            $table->date('rencana_selesai')->nullable();
            $table->foreignUuid('cycle_id')->nullable()->constrained('supervision_cycles')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['annual_program_id', 'guru_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_targets');
        Schema::dropIfExists('annual_programs');
    }
};

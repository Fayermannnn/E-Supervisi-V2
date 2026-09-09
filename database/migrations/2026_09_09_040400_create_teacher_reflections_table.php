<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refleksi diri guru (M1). Tahap: pra_observasi | pasca_umpan_balik.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_reflections', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cycle_id')->constrained('supervision_cycles')->cascadeOnDelete();
            $table->foreignUuid('guru_id')->constrained('users')->restrictOnDelete();
            $table->string('tahap');
            $table->longText('konten');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['cycle_id', 'tahap']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_reflections');
    }
};

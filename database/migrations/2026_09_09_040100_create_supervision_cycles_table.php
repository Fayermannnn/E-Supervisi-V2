<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jantung sistem — siklus supervisi (Spec §5, §7). Status = App\Support\Enums\CycleStatus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervision_cycles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('guru_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('supervisor_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('sekolah_id')->constrained('sekolah')->restrictOnDelete();
            $table->foreignUuid('dinas_id')->constrained('dinas')->restrictOnDelete();
            $table->string('tahun_ajaran'); // mis. 2026/2027
            $table->string('semester'); // ganjil | genap
            $table->string('judul');
            $table->text('fokus_ringkas')->nullable();
            $table->unsignedTinyInteger('status')->default(0);
            $table->text('canceled_reason')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['supervisor_id', 'status']);
            $table->index(['guru_id', 'status']);
            $table->index(['dinas_id', 'tahun_ajaran', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervision_cycles');
    }
};

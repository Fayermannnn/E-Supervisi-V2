<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pelaporan berbasis data (M6, prioritas tinggi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('scope'); // cycle | guru | sekolah | dinas
            $table->uuid('scope_id')->nullable();
            $table->string('tipe')->default('ringkasan');
            $table->date('periode_mulai')->nullable();
            $table->date('periode_selesai')->nullable();
            $table->jsonb('filter')->nullable();
            $table->foreignUuid('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('format')->default('pdf'); // pdf | xlsx | csv
            $table->string('file_disk')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->default('antre'); // antre | siap | gagal
            $table->timestamps();

            $table->index(['scope', 'scope_id']);
        });

        Schema::create('report_snapshots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('report_id')->constrained('reports')->cascadeOnDelete();
            $table->jsonb('data');
            $table->timestamp('generated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_snapshots');
        Schema::dropIfExists('reports');
    }
};

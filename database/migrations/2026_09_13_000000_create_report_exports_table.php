<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Berkas ekspor laporan (M6). Sebuah `report` dapat memiliki beberapa berkas
 * ekspor (PDF / XLSX / CSV). Dibangkitkan lewat job (`GenerateReportExport`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_exports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('report_id')->constrained('reports')->cascadeOnDelete();
            $table->string('format'); // pdf | xlsx | csv
            $table->string('disk')->nullable();
            $table->string('path')->nullable();
            $table->unsignedBigInteger('ukuran')->nullable(); // byte
            $table->string('status')->default('antre'); // antre | diproses | siap | gagal
            $table->text('error')->nullable();
            $table->foreignUuid('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('selesai_at')->nullable();
            $table->timestamps();

            $table->index(['report_id', 'format']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};

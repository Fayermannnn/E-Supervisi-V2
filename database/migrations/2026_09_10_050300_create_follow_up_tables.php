<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tindak lanjut / RTL (M5, prioritas tinggi). Mata rantai terlemah supervisi
 * konvensional — dukungan tenggat, pengingat, bukti, eskalasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_up_plans', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cycle_id')->constrained('supervision_cycles')->cascadeOnDelete();
            $table->text('tujuan');
            $table->foreignUuid('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->date('mulai');
            $table->date('tenggat');
            $table->string('status')->default('berjalan'); // berjalan | terlambat | selesai | dibatalkan
            $table->timestamp('selesai_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'tenggat']);
            $table->index('cycle_id');
        });

        Schema::create('follow_up_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('follow_up_plan_id')->constrained('follow_up_plans')->cascadeOnDelete();
            $table->text('deskripsi');
            $table->text('indikator_keberhasilan');
            $table->date('tenggat_item')->nullable();
            $table->string('status')->default('belum'); // belum | berjalan | selesai
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamp('selesai_at')->nullable();
            $table->timestamps();
        });

        Schema::create('follow_up_evidence', function (Blueprint $table): void {
            $table->uuid('id')->primary(); // UUID dibuat klien (offline)
            $table->foreignUuid('follow_up_item_id')->constrained('follow_up_items')->cascadeOnDelete();
            $table->foreignUuid('diunggah_oleh')->constrained('users')->restrictOnDelete();
            $table->string('tipe'); // dokumen | foto | tautan | catatan
            $table->string('disk')->nullable();
            $table->string('path')->nullable();
            $table->string('original_name')->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('url')->nullable();
            $table->string('upload_status')->default('pending');
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_up_evidence');
        Schema::dropIfExists('follow_up_items');
        Schema::dropIfExists('follow_up_plans');
    }
};

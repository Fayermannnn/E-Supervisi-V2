<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengembangan Profesional Guru (M9 Katalog PKB + M10 Perpustakaan Praktik
 * Baik). @provisional — struktur dapat berubah additive setelah SLR Gate 6/7.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pkb_catalog_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('judul');
            $table->text('deskripsi');
            $table->string('penyelenggara')->nullable();
            $table->string('tipe')->default('mandiri'); // pelatihan | mandiri | kkg | webinar | bacaan | lainnya
            $table->string('tautan')->nullable();
            $table->jsonb('tags')->nullable();        // list<string> — dicocokkan dengan area pengembangan
            $table->jsonb('kompetensi')->nullable();  // list<string>
            $table->unsignedInteger('durasi_jam')->nullable();
            $table->foreignUuid('pemilik_dinas_id')->nullable()->constrained('dinas')->nullOnDelete(); // null = global
            $table->string('status')->default('draft'); // draft | terbit | arsip
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['status', 'tipe']);
            $table->index('pemilik_dinas_id');
        });

        Schema::create('pkb_recommendations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cycle_id')->constrained('supervision_cycles')->cascadeOnDelete();
            $table->foreignUuid('guru_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('pkb_catalog_item_id')->constrained('pkb_catalog_items')->cascadeOnDelete();
            $table->string('sumber')->default('analisis'); // analisis | rtl_berulang | manual
            $table->text('alasan');
            $table->string('status')->default('disarankan'); // disarankan | dipilih | ditolak | selesai
            $table->foreignUuid('direkomendasikan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('direspons_at')->nullable();
            $table->timestamps();

            $table->unique(['cycle_id', 'pkb_catalog_item_id']);
            $table->index(['guru_id', 'status']);
        });

        Schema::create('best_practices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cycle_id')->constrained('supervision_cycles')->cascadeOnDelete();
            $table->foreignUuid('guru_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('dinas_id')->constrained('dinas')->restrictOnDelete();
            $table->foreignUuid('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->foreignUuid('nominated_by')->constrained('users')->restrictOnDelete();
            $table->string('judul');
            $table->text('ringkasan');
            $table->text('praktik');
            $table->jsonb('tags')->nullable();
            $table->string('skor_band')->nullable();
            $table->boolean('anonim')->default(false);
            $table->foreignUuid('consent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('consent_at')->nullable();
            $table->foreignUuid('curated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('curated_at')->nullable();
            $table->text('catatan_kurasi')->nullable();
            // menunggu_consent | menunggu_kurasi | terbit | ditolak | ditarik
            $table->string('status')->default('menunggu_consent');
            $table->timestamp('terbit_at')->nullable();
            $table->timestamps();

            $table->unique('cycle_id');
            $table->index(['dinas_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('best_practices');
        Schema::dropIfExists('pkb_recommendations');
        Schema::dropIfExists('pkb_catalog_items');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bank instrumen Format A–E (M8, ADR-013). Skema item generik & versioned;
 * struktur item final menunggu validasi Artikel 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instruments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code'); // A|B|C|D|E atau slug kustom
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->foreignUuid('pemilik_dinas_id')->nullable()->constrained('dinas')->cascadeOnDelete();
            $table->string('status')->default('draft'); // draft | published | archived
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['pemilik_dinas_id', 'code']);
            $table->index('status');
        });

        DB::statement('CREATE UNIQUE INDEX instruments_global_code_unique ON instruments (code) WHERE pemilik_dinas_id IS NULL AND deleted_at IS NULL');

        Schema::create('instrument_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('instrument_id')->constrained('instruments')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->jsonb('schema_json');
            $table->jsonb('scoring_config')->nullable();
            $table->text('catatan_perubahan')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['instrument_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instrument_versions');
        Schema::dropIfExists('instruments');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Konfigurasi kebijakan key-value (M15). Baris dengan dinas_id = null adalah
 * default global; baris dengan dinas_id menimpa default untuk dinas tersebut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('dinas_id')->nullable()->constrained('dinas')->cascadeOnDelete();
            $table->string('key');
            $table->jsonb('value');
            $table->string('description')->nullable();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['dinas_id', 'key']);
        });

        // Satu baris global (dinas_id IS NULL) per key — Postgres menganggap
        // NULL berbeda pada unique index biasa, jadi butuh partial index.
        DB::statement('CREATE UNIQUE INDEX policy_settings_global_key_unique ON policy_settings (key) WHERE dinas_id IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_settings');
    }
};

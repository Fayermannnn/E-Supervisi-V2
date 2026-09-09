<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Observasi pembelajaran (M2, ADR-006). `observations.id` di-generate klien
 * agar sinkronisasi luring idempoten. `version` = optimistic lock.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observations', function (Blueprint $table): void {
            $table->uuid('id')->primary(); // UUID dibuat klien
            $table->foreignUuid('cycle_id')->constrained('supervision_cycles')->cascadeOnDelete();
            $table->foreignUuid('observer_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('instrument_version_id')->constrained('instrument_versions')->restrictOnDelete();
            $table->string('tipe'); // sinkron | asinkron
            $table->timestamp('mulai_at')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->longText('catatan_skrip')->nullable();
            $table->string('status')->default('draft'); // draft | final
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('client_updated_at')->nullable();
            $table->string('device_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['cycle_id', 'status']);
        });

        Schema::create('observation_responses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('observation_id')->constrained('observations')->cascadeOnDelete();
            $table->foreignUuid('instrument_version_id')->constrained('instrument_versions')->restrictOnDelete();
            $table->string('section_key');
            $table->string('item_key');
            $table->decimal('value_numeric', 10, 2)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->text('value_text')->nullable();
            $table->jsonb('value_json')->nullable();
            $table->text('catatan_item')->nullable();
            $table->timestamps();

            $table->unique(['observation_id', 'item_key']);
        });

        Schema::create('observation_media', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('observation_id')->constrained('observations')->cascadeOnDelete();
            $table->string('tipe'); // video | audio | foto | dokumen
            $table->string('disk')->nullable();
            $table->string('path')->nullable(); // null selama menunggu unggah
            $table->string('original_name');
            $table->unsignedBigInteger('size')->nullable();
            $table->string('checksum')->nullable();
            $table->string('upload_status')->default('pending'); // pending | uploading | stored | failed
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
        });

        Schema::create('observation_sync_log', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('observation_id')->constrained('observations')->cascadeOnDelete();
            $table->string('device_id')->nullable();
            $table->string('action'); // push | pull | conflict
            $table->unsignedInteger('client_version')->nullable();
            $table->unsignedInteger('server_version')->nullable();
            $table->boolean('resolved')->default(true);
            $table->string('payload_hash')->nullable();
            $table->timestamp('created_at')->index();

            $table->index('observation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observation_sync_log');
        Schema::dropIfExists('observation_media');
        Schema::dropIfExists('observation_responses');
        Schema::dropIfExists('observations');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat transisi status siklus (append-only, docs/state-machine.md ADR-004).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cycle_status_transitions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cycle_id')->constrained('supervision_cycles')->cascadeOnDelete();
            $table->unsignedTinyInteger('from_status');
            $table->unsignedTinyInteger('to_status');
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role')->nullable();
            $table->text('reason')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('created_at')->index();

            $table->index('cycle_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_status_transitions');
    }
};

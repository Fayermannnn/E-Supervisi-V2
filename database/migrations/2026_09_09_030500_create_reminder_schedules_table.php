<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_schedules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuidMorphs('remindable');
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('kind'); // jenis pengingat, mis. followup.due_soon
            $table->string('channel')->default('app'); // app | mail
            $table->timestamp('send_at')->index();
            $table->string('status')->default('scheduled'); // scheduled | sent | canceled
            $table->unsignedTinyInteger('escalation_level')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->jsonb('payload')->nullable();
            $table->timestamps();

            $table->index(['status', 'send_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_schedules');
    }
};

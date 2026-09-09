<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisor_assignments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('supervisor_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('guru_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('mulai');
            $table->date('selesai')->nullable();
            $table->timestamps();

            $table->index(['supervisor_id', 'guru_id']);
            $table->index('guru_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_assignments');
    }
};

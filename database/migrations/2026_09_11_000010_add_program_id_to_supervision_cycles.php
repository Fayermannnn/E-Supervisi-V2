<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menautkan siklus ke program tahunan yang menyemainya (M7). Nullable —
 * siklus dapat dibuat manual tanpa program.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supervision_cycles', function (Blueprint $table): void {
            $table->foreignUuid('program_id')->nullable()->after('dinas_id')
                ->constrained('annual_programs')->nullOnDelete();
            $table->index('program_id');
        });
    }

    public function down(): void
    {
        Schema::table('supervision_cycles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('program_id');
        });
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dinas', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->string('kode')->unique();
            $table->string('tipe'); // kabupaten | kota
            $table->string('provinsi');
            $table->timestamps();
        });

        Schema::create('sekolah', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('dinas_id')->constrained('dinas')->restrictOnDelete();
            $table->string('nama');
            $table->string('npsn')->nullable()->unique();
            $table->string('jenjang'); // SD | SMP | SMA | SMK | PAUD | SLB
            $table->string('kecamatan')->nullable();
            $table->string('wilayah')->nullable();
            $table->string('alamat')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['dinas_id', 'jenjang']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sekolah');
        Schema::dropIfExists('dinas');
    }
};

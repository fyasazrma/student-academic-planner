<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kelas_mata_kuliah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliah')->restrictOnDelete();
            $table->foreignId('tahun_akademik_id')->constrained('tahun_akademik')->restrictOnDelete();
            $table->foreignId('dosen_id')->constrained('users')->restrictOnDelete();
            $table->string('kode_kelas', 10);
            $table->string('nama_kelas', 100)->nullable();
            $table->unsignedSmallInteger('kapasitas')->nullable();
            $table->timestamps();

            $table->unique(
                ['mata_kuliah_id', 'tahun_akademik_id', 'kode_kelas'],
                'kelas_mata_kuliah_unik'
            );
            $table->index('dosen_id');
            $table->index('tahun_akademik_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas_mata_kuliah');
    }
};

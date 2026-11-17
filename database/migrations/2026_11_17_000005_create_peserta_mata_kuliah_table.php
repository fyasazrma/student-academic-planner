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
        Schema::create('peserta_mata_kuliah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_mata_kuliah_id')->constrained('kelas_mata_kuliah')->restrictOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 15)->default('aktif');
            $table->date('tanggal_daftar')->nullable();
            $table->timestamps();

            $table->unique(
                ['kelas_mata_kuliah_id', 'mahasiswa_id'],
                'peserta_mata_kuliah_unik'
            );
            $table->index('mahasiswa_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta_mata_kuliah');
    }
};

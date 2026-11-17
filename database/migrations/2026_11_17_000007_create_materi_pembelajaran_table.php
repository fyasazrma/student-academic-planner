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
        Schema::create('materi_pembelajaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_mata_kuliah_id')->constrained('kelas_mata_kuliah')->restrictOnDelete();
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->string('judul', 200);
            $table->text('deskripsi')->nullable();
            $table->string('file_path', 500);
            $table->string('file_name', 255);
            $table->string('file_mime', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedSmallInteger('pertemuan_ke')->nullable();
            $table->dateTime('dipublikasikan_pada')->nullable();
            $table->timestamps();

            $table->index(['kelas_mata_kuliah_id', 'dipublikasikan_pada'], 'materi_kelas_publikasi_index');
            $table->index('dibuat_oleh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materi_pembelajaran');
    }
};

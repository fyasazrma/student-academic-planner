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
        Schema::create('agenda_akademik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->foreignId('kelas_mata_kuliah_id')->nullable()->constrained('kelas_mata_kuliah')->restrictOnDelete();
            $table->string('judul', 200);
            $table->text('deskripsi')->nullable();
            $table->string('jenis', 20);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->time('waktu_mulai')->nullable();
            $table->time('waktu_selesai')->nullable();
            $table->string('lokasi', 150)->nullable();
            $table->dateTime('dipublikasikan_pada')->nullable();
            $table->timestamps();

            $table->index(['tanggal_mulai', 'tanggal_selesai']);
            $table->index('jenis');
            $table->index('kelas_mata_kuliah_id');
            $table->index('dibuat_oleh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agenda_akademik');
    }
};

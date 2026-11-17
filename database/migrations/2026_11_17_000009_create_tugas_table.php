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
        Schema::create('tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_mata_kuliah_id')->constrained('kelas_mata_kuliah')->restrictOnDelete();
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->string('judul', 200);
            $table->text('deskripsi')->nullable();
            $table->string('lampiran_path', 500)->nullable();
            $table->string('lampiran_nama', 255)->nullable();
            $table->string('lampiran_mime', 100)->nullable();
            $table->unsignedBigInteger('lampiran_size')->nullable();
            $table->dateTime('dipublikasikan_pada')->nullable();
            $table->dateTime('deadline');
            $table->decimal('nilai_maksimal', 5, 2)->default(100);
            $table->boolean('izinkan_terlambat')->default(false);
            $table->timestamps();

            $table->index(['kelas_mata_kuliah_id', 'deadline']);
            $table->index('dipublikasikan_pada');
            $table->index('dibuat_oleh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tugas');
    }
};

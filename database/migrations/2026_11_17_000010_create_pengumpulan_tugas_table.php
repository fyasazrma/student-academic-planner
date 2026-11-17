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
        Schema::create('pengumpulan_tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tugas_id')->constrained('tugas')->restrictOnDelete();
            $table->foreignId('peserta_mata_kuliah_id')->constrained('peserta_mata_kuliah')->restrictOnDelete();
            $table->string('file_path', 500)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->string('file_mime', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('catatan')->nullable();
            $table->dateTime('dikumpulkan_pada');
            $table->boolean('terlambat')->default(false);
            $table->decimal('nilai', 5, 2)->nullable();
            $table->text('umpan_balik')->nullable();
            $table->foreignId('dinilai_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('dinilai_pada')->nullable();
            $table->timestamps();

            $table->unique(
                ['tugas_id', 'peserta_mata_kuliah_id'],
                'pengumpulan_tugas_unik'
            );
            $table->index('peserta_mata_kuliah_id');
            $table->index('dikumpulkan_pada');
            $table->index('dinilai_pada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengumpulan_tugas');
    }
};

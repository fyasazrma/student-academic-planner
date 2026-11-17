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
        Schema::create('progres_materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materi_pembelajaran_id')->constrained('materi_pembelajaran')->cascadeOnDelete();
            $table->foreignId('peserta_mata_kuliah_id')->constrained('peserta_mata_kuliah')->cascadeOnDelete();
            $table->string('status', 20)->default('belum_dimulai');
            $table->unsignedTinyInteger('progres')->default(0);
            $table->dateTime('selesai_pada')->nullable();
            $table->timestamps();

            $table->unique(
                ['materi_pembelajaran_id', 'peserta_mata_kuliah_id'],
                'progres_materi_unik'
            );
            $table->index('peserta_mata_kuliah_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progres_materi');
    }
};

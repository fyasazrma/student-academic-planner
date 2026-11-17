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
        Schema::create('absensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesi_absensi_id')->constrained('sesi_absensi')->restrictOnDelete();
            $table->foreignId('peserta_mata_kuliah_id')->constrained('peserta_mata_kuliah')->restrictOnDelete();
            $table->string('status', 15);
            $table->string('keterangan', 255)->nullable();
            $table->string('metode', 15)->default('manual');
            $table->dateTime('dicatat_pada');
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['sesi_absensi_id', 'peserta_mata_kuliah_id'],
                'absensi_unik'
            );
            $table->index('peserta_mata_kuliah_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};

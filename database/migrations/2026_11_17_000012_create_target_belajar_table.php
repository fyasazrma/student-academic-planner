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
        Schema::create('target_belajar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('users')->cascadeOnDelete();
            $table->string('judul', 200);
            $table->text('deskripsi')->nullable();
            $table->date('tanggal_target')->nullable();
            $table->string('status', 20)->default('belum_selesai');
            $table->unsignedTinyInteger('progres')->default(0);
            $table->dateTime('selesai_pada')->nullable();
            $table->timestamps();

            $table->index(['mahasiswa_id', 'tanggal_target']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target_belajar');
    }
};

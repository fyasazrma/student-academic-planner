<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jejak koreksi absensi. Baris audit tidak pernah dihapus atau ditimpa.
     * Seluruh relasi nullable memakai ON DELETE SET NULL agar riwayat tetap
     * terbaca bila data transaksi dihapus.
     */
    public function up(): void
    {
        Schema::create('riwayat_perubahan_absensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('absensi_id')
                ->nullable()
                ->constrained('absensi')
                ->nullOnDelete();
            $table->foreignId('peserta_mata_kuliah_id')
                ->nullable()
                ->constrained('peserta_mata_kuliah')
                ->nullOnDelete();
            $table->foreignId('sesi_absensi_id')
                ->nullable()
                ->constrained('sesi_absensi')
                ->nullOnDelete();
            $table->string('status_sebelum', 15)->nullable();
            $table->string('status_sesudah', 15);
            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('alasan');
            $table->timestamps();

            $table->index('absensi_id');
            $table->index(['peserta_mata_kuliah_id', 'sesi_absensi_id'], 'riwayat_absensi_peserta_sesi_index');
            $table->index('sesi_absensi_id');
            $table->index('actor_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_perubahan_absensi');
    }
};

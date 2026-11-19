<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jejak setiap pembuatan dan perubahan nilai UTS/UAS, termasuk perubahan
     * konfigurasi komponen tugas pada kelas. Baris audit tidak pernah dihapus
     * atau ditimpa saat nilai diubah kembali.
     */
    public function up(): void
    {
        Schema::create('riwayat_perubahan_nilai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nilai_id')
                ->nullable()
                ->constrained('nilai')
                ->nullOnDelete();
            $table->foreignId('peserta_mata_kuliah_id')
                ->nullable()
                ->constrained('peserta_mata_kuliah')
                ->nullOnDelete();
            $table->string('komponen', 20);
            $table->decimal('nilai_sebelum', 5, 2)->nullable();
            $table->decimal('nilai_sesudah', 5, 2)->nullable();
            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('alasan')->nullable();
            $table->timestamps();

            $table->index('nilai_id');
            $table->index(['peserta_mata_kuliah_id', 'komponen']);
            $table->index('actor_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_perubahan_nilai');
    }
};

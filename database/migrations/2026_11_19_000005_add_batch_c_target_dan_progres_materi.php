<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch C — Target Belajar dan Progres Materi.
 *
 * Migration additive: tidak menghapus data lama dan tidak mengubah kolom yang
 * sudah dipakai Batch A/B. Kolom status lama `belum_selesai` tetap dipertahankan
 * sebagai nilai "Belum dimulai" agar data existing tetap terbaca.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('target_belajar', function (Blueprint $table): void {
            $table->foreignId('mata_kuliah_id')
                ->nullable()
                ->after('mahasiswa_id')
                ->constrained('mata_kuliah')
                ->nullOnDelete();
            $table->string('satuan', 30)->nullable()->after('deskripsi');
            $table->decimal('target_nilai', 12, 2)->nullable()->after('satuan');
            $table->decimal('capaian', 12, 2)->default(0)->after('target_nilai');
            $table->date('tanggal_mulai')->nullable()->after('capaian');
            $table->date('deadline')->nullable()->after('tanggal_mulai');

            $table->index(['mahasiswa_id', 'status'], 'target_belajar_mahasiswa_status_index');
            $table->index(['mahasiswa_id', 'deadline'], 'target_belajar_mahasiswa_deadline_index');
        });

        Schema::create('aktivitas_materi', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('materi_pembelajaran_id')
                ->constrained('materi_pembelajaran')
                ->cascadeOnDelete();
            $table->foreignId('peserta_mata_kuliah_id')
                ->constrained('peserta_mata_kuliah')
                ->cascadeOnDelete();
            $table->string('jenis', 20);
            $table->timestamps();

            $table->index(
                ['peserta_mata_kuliah_id', 'materi_pembelajaran_id', 'jenis'],
                'aktivitas_materi_ringkas_index'
            );
            $table->index(['materi_pembelajaran_id', 'created_at'], 'aktivitas_materi_waktu_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aktivitas_materi');

        Schema::table('target_belajar', function (Blueprint $table): void {
            $table->dropIndex('target_belajar_mahasiswa_deadline_index');
            $table->dropIndex('target_belajar_mahasiswa_status_index');
            $table->dropColumn([
                'deadline',
                'tanggal_mulai',
                'capaian',
                'target_nilai',
                'satuan',
                'mata_kuliah_id',
            ]);
        });
    }
};

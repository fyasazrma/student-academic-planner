<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Komponen tugas ditentukan eksplisit per kelas oleh dosen.
     *
     * Null untuk kelas lama berarti "belum ditentukan", bukan "digunakan" dan
     * bukan pula "tidak digunakan". Nilai akhir tidak dihitung selama status
     * ini belum dipilih, sehingga data lama tidak diberi arti baru.
     */
    public function up(): void
    {
        Schema::table('kelas_mata_kuliah', function (Blueprint $table): void {
            $table->boolean('gunakan_tugas')
                ->nullable()
                ->after('status_pendaftaran');
        });
    }

    /**
     * Reverse only the column added by this migration.
     */
    public function down(): void
    {
        Schema::table('kelas_mata_kuliah', function (Blueprint $table): void {
            $table->dropColumn('gunakan_tugas');
        });
    }
};

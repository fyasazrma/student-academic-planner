<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add an optional hashed join key and an explicit enrollment status to classes.
     *
     * Existing rows receive a null key and the default "tutup" status. No existing
     * column is modified or removed.
     */
    public function up(): void
    {
        Schema::table('kelas_mata_kuliah', function (Blueprint $table): void {
            $table->string('kode_gabung_hash', 64)
                ->nullable()
                ->unique('kelas_mata_kuliah_kode_gabung_hash_unique');

            $table->string('status_pendaftaran', 15)->default('tutup');
        });
    }

    /**
     * Reverse only the columns and index added by this migration.
     */
    public function down(): void
    {
        Schema::table('kelas_mata_kuliah', function (Blueprint $table): void {
            $table->dropUnique('kelas_mata_kuliah_kode_gabung_hash_unique');
            $table->dropColumn(['kode_gabung_hash', 'status_pendaftaran']);
        });
    }
};

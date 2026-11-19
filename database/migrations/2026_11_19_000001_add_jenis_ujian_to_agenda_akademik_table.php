<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jadwal UTS/UAS sudah memakai agenda_akademik dengan jenis = ujian.
     * Kolom ini hanya menandai tipe ujian tanpa mengubah data lama yang
     * tidak memiliki penanda UTS/UAS.
     */
    public function up(): void
    {
        Schema::table('agenda_akademik', function (Blueprint $table): void {
            $table->string('jenis_ujian', 10)->nullable()->after('jenis');
        });
    }

    public function down(): void
    {
        Schema::table('agenda_akademik', function (Blueprint $table): void {
            $table->dropColumn('jenis_ujian');
        });
    }
};

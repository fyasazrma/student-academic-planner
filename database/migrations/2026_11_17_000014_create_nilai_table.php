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
        Schema::create('nilai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_mata_kuliah_id')->constrained('peserta_mata_kuliah')->restrictOnDelete();
            $table->string('komponen', 20);
            $table->decimal('nilai', 5, 2);
            $table->decimal('bobot', 5, 2)->nullable();
            $table->foreignId('dinilai_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('dinilai_pada')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(
                ['peserta_mata_kuliah_id', 'komponen'],
                'nilai_unik'
            );
            $table->index('dinilai_oleh');
            $table->index('komponen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nilai');
    }
};

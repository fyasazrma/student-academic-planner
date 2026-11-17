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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->after('email')->index();
            $table->string('nim', 20)->nullable()->unique()->after('role');
            $table->string('nidn', 20)->nullable()->unique()->after('nim');
            $table->string('program_studi', 100)->nullable()->after('nidn');
            $table->unsignedSmallInteger('angkatan')->nullable()->after('program_studi');
            $table->string('no_telepon', 20)->nullable()->after('angkatan');
            $table->string('foto_path', 255)->nullable()->after('no_telepon');
            $table->boolean('is_active')->default(true)->index()->after('foto_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nim']);
            $table->dropUnique(['nidn']);
            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);
            $table->dropColumn([
                'role',
                'nim',
                'nidn',
                'program_studi',
                'angkatan',
                'no_telepon',
                'foto_path',
                'is_active',
            ]);
        });
    }
};

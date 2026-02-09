<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambahkan kolom member_id di tabel kendaraan
        Schema::table('kendaraan', function (Blueprint $table) {
            $table->foreignId('member_id')
                  ->nullable()
                  ->after('slot_parkir_id')
                  ->constrained('member')
                  ->nullOnDelete();
        });

        // 2. Migrasi data: pindahkan relasi dari member.kendaraan_id ke kendaraan.member_id
        DB::statement('
            UPDATE kendaraan k
            INNER JOIN member m ON m.kendaraan_id = k.id AND m.deleted_at IS NULL
            SET k.member_id = m.id
        ');

        // 3. Hapus kolom kendaraan_id dari member
        Schema::table('member', function (Blueprint $table) {
            $table->dropForeign(['kendaraan_id']);
            $table->dropColumn('kendaraan_id');
        });
    }

    public function down(): void
    {
        // 1. Tambahkan kembali kendaraan_id di member
        Schema::table('member', function (Blueprint $table) {
            $table->foreignId('kendaraan_id')
                  ->nullable()
                  ->after('no_hp')
                  ->constrained('kendaraan')
                  ->cascadeOnDelete();
        });

        // 2. Migrasi data balik
        DB::statement('
            UPDATE member m
            INNER JOIN kendaraan k ON k.member_id = m.id
            SET m.kendaraan_id = k.id
        ');

        // 3. Hapus member_id dari kendaraan
        Schema::table('kendaraan', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->dropColumn('member_id');
        });
    }
};

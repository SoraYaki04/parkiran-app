<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kendaraan', function (Blueprint $table) {
            $table->foreignId('slot_parkir_id')
                  ->nullable()
                  ->after('tipe_kendaraan_id')
                  ->constrained('slot_parkir')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kendaraan', function (Blueprint $table) {
            $table->dropForeign(['slot_parkir_id']);
            $table->dropColumn('slot_parkir_id');
        });
    }
};

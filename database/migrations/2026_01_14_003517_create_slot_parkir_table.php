<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slot_parkir', function (Blueprint $table) {
            $table->id();

            // Relasi ke area parkir
            $table->foreignId('area_id')
                  ->constrained('area_parkir')
                  ->cascadeOnDelete();

            // Identitas slot
            $table->string('kode_slot'); // A1, B2, C3
            $table->string('baris');     // A, B, C
            $table->integer('kolom');    // 1, 2, 3

            // Relasi tipe kendaraan
            $table->foreignId('tipe_kendaraan_id')
                  ->constrained('tipe_kendaraan');

            // Status slot
            $table->enum('status', ['kosong', 'terisi'])
                  ->default('kosong');

            $table->timestamps();

            // Mencegah duplikasi slot dalam 1 area
            $table->unique(['area_id', 'kode_slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slot_parkir');
    }
};

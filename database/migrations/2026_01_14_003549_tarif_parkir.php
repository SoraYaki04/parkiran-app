<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarif_parkir', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipe_kendaraan_id')
                ->constrained('tipe_kendaraan')
                ->cascadeOnDelete();

            $table->integer('durasi_min'); // menit
            $table->integer('durasi_max'); // menit
            $table->integer('tarif');

            $table->softDeletes();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tarif_parkir');
    }
};

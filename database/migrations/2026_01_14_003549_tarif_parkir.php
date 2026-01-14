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
            $table->foreignId('tipe_kendaraan_id')->constrained('tipe_kendaraan');
            $table->integer('durasi_min');
            $table->integer('durasi_max');
            $table->integer('tarif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarif_parkir');
    }
};

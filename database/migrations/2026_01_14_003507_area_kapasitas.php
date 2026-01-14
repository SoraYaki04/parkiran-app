<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('area_kapasitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('area_parkir')->cascadeOnDelete();
            $table->foreignId('tipe_kendaraan_id')->constrained('tipe_kendaraan');
            $table->integer('kapasitas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_kapasitas');
    }
};

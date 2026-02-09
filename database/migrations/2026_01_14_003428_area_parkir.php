<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('area_parkir', function (Blueprint $table) {
            $table->id();
            $table->string('kode_area')->unique();
            $table->string('nama_area');
            $table->string('lokasi_fisik');
            $table->integer('kapasitas_total');

            $table->enum('status', ['aktif', 'maintenance'])
                ->default('aktif');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_parkir');
    }
};

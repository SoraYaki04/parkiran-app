<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kendaraan', function (Blueprint $table) {
            $table->id();
            $table->string('plat_nomor')->unique();
            $table->foreignId('tipe_kendaraan_id')->constrained('tipe_kendaraan');
            $table->string('nama_pemilik');
            $table->string('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kendaraan');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_parkir', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kendaraan_id')->constrained('kendaraan');
            $table->foreignId('area_id')->constrained('area_parkir');
            $table->dateTime('jam_masuk');
            $table->dateTime('jam_keluar')->nullable();
            $table->integer('durasi_menit')->nullable();
            $table->string('status'); // IN / OUT
            $table->foreignId('user_masuk_id')->constrained('users');
            $table->foreignId('user_keluar_id')->nullable()->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_parkir');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
    Schema::create('activity_log', function (Blueprint $table) {
        $table->id();

        $table->foreignId('user_id')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        // jenis aksi (kode tetap)
        $table->string('action'); 
        // contoh: LOGIN, LOGOUT, TRANSAKSI_MASUK, TRANSAKSI_KELUAR, CETAK_STRUK

        // kategori besar
        $table->string('category')->nullable();
        // SYSTEM | TRANSAKSI | MASTER | PEMBAYARAN

        // deskripsi manusia
        $table->text('description')->nullable();
        // "Transaksi masuk kendaraan B 1234 CD"

        // target utama
        $table->string('target')->nullable();
        // plat nomor / nama data / ID transaksi

        $table->timestamps();

        $table->index(['action', 'category']);
        $table->index('created_at');
    });

    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_parkir_id')->constrained('transaksi_parkir')->cascadeOnDelete();
            $table->integer('tarif_dasar');
            $table->integer('diskon_persen')->default(0);
            $table->integer('diskon_nominal')->default(0);
            $table->integer('total_bayar');
            $table->string('metode_pembayaran');
            $table->integer('jumlah_bayar')->nullable();
            $table->integer('kembalian')->nullable();
            $table->dateTime('tanggal_bayar');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};

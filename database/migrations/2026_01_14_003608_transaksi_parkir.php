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

            // kode karcis unik (QR / barcode / random)
            $table->string('kode_karcis')->unique();

            $table->foreignId('tipe_kendaraan_id')
                ->constrained('tipe_kendaraan');

            $table->timestamp('waktu_masuk');
            $table->timestamp('waktu_keluar')->nullable();

            $table->integer('durasi_menit')->nullable();
            $table->integer('total_bayar')->nullable();

            $table->foreignId('member_id')
                ->nullable()
                ->constrained('member');

            $table->enum('status', ['IN', 'OUT']);

            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_parkir');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transaksi_parkir', function (Blueprint $table) {
            $table->id();

            $table->string('kode_karcis')->unique();

            $table->foreignId('parkir_session_id')
                ->nullable()
                ->constrained('parkir_sessions')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('kendaraan_id')
                ->constrained('kendaraan')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('slot_parkir_id')
                ->nullable()
                ->constrained('slot_parkir')
                ->nullOnDelete();

            $table->foreignId('tipe_kendaraan_id')
                ->constrained('tipe_kendaraan')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamp('waktu_masuk');
            $table->timestamp('waktu_keluar')->nullable();

            $table->integer('durasi_menit')->nullable();
            $table->integer('total_bayar')->nullable();

            $table->foreignId('member_id')
                ->nullable()
                ->constrained('member')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('operator')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_parkir');
    }
};

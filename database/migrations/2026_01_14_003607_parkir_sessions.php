<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('parkir_sessions', function (Blueprint $table) {
            $table->id();

            // Token yang akan dimasukkan ke QR Code
            $table->string('token')->unique();

            // Tipe kendaraan (motor, mobil, dll)
            $table->foreignId('tipe_kendaraan_id')
                ->constrained('tipe_kendaraan')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Data yang diisi user via HP
            $table->string('plat_nomor')->nullable();
            $table->foreignId('slot_parkir_id')
                ->nullable()
                ->constrained('slot_parkir')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // Status alur parkir
            $table->enum('status', [
                'WAITING_INPUT',   // QR baru dibuat
                'SCANNED',  // 
                'IN_PROGRESS',     // mengisi data
                'FINISHED',     // palang terbuka / sudah masuk
                'CANCELLED',       // dibatalkan / expired
            ])->default('WAITING_INPUT');

            // Waktu penting
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamp('expired_at')->nullable();

            $table->string('exit_token')->nullable()->unique();
            $table->timestamp('confirmed_at')->nullable();


            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parkir_sessions');
    }
};

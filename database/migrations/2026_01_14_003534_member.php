<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member', function (Blueprint $table) {
            $table->id();

            $table->string('kode_member')->unique();
            $table->string('nama');
            $table->string('no_hp');

            $table->foreignId('kendaraan_id')
                ->unique()
                ->constrained('kendaraan')
                ->cascadeOnDelete();

            $table->foreignId('tier_member_id')
                ->constrained('tier_member');

            $table->date('tanggal_mulai');
            $table->date('tanggal_berakhir');

            $table->enum('status', ['aktif', 'nonaktif', 'expired'])
                ->default('aktif');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member');
    }
};

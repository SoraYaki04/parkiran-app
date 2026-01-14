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
            $table->foreignId('kendaraan_id')->constrained('kendaraan')->cascadeOnDelete();
            $table->string('tipe_member'); // Regular, Silver, Gold
            $table->integer('diskon_persen');
            $table->date('tanggal_mulai');
            $table->date('tanggal_akhir');
            $table->string('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member');
    }
};

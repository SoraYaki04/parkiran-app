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
            $table->string('nama')->nullable();
            $table->string('no_hp')->unique(); // kunci utama cek member
            $table->string('tipe_member'); // Regular, Silver, Gold
            $table->integer('diskon_persen');
            $table->date('tanggal_mulai');
            $table->date('tanggal_akhir');
            $table->enum('status', ['aktif', 'nonaktif']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member');
    }
};

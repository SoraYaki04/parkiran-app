<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tier_member', function (Blueprint $table) {
            $table->id();

            $table->string('nama')->unique();                 // Silver, Gold, Platinum
            $table->integer('harga');                         // 100000, 200000, 1500000
            $table->enum('periode', ['bulanan', 'tahunan']);  // billing
            $table->integer('diskon_persen');                 // 5, 10, 15

            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tier_memberr');
    }
};

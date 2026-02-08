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
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tier')->nullable(); 
            $table->foreignId('vehicle_type_id')->nullable()->constrained('tipe_kendaraan')->nullOnDelete();
            $table->integer('priority')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('days_of_week')->nullable();
            $table->enum('type', ['TIME_BASED', 'DURATION_BASED', 'FLAT'])->default('TIME_BASED');
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};

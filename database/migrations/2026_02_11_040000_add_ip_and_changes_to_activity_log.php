<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('description');
            $table->json('old_values')->nullable()->after('ip_address');
            $table->json('new_values')->nullable()->after('old_values');
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'old_values', 'new_values']);
        });
    }
};

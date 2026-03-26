<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('reference_table', 100)->nullable()->after('description');
            $table->unsignedBigInteger('reference_id')->nullable()->after('reference_table');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn(['reference_table', 'reference_id']);
        });
    }
};
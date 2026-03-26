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
        if (Schema::hasTable('messages') && ! Schema::hasColumn('messages', 'sender')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->string('sender', 50)->default('user')->after('messages');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('messages') && Schema::hasColumn('messages', 'sender')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->dropColumn('sender');
            });
        }
    }
};

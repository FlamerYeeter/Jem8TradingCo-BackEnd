<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // ✅ Drop the old foreign key pointing to users
            $table->dropForeign(['user_id']);

            // ✅ Add new foreign key pointing to accounts
            $table->foreign('user_id')
                  ->references('id')
                  ->on('accounts')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }
};
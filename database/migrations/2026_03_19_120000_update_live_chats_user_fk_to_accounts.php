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
        Schema::table('live_chats', function (Blueprint $table) {
            // Drop existing foreign key on user_id (if present)
            try {
                $table->dropForeign(['user_id']);
            } catch (\Exception $e) {
                // ignore if not exists
            }

            // Recreate foreign key referencing accounts.id
            $table->foreign('user_id')
                  ->references('id')
                  ->on('accounts')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('live_chats', function (Blueprint $table) {
            try {
                $table->dropForeign(['user_id']);
            } catch (\Exception $e) {
                // ignore if not exists
            }

            // Restore foreign key to users.id
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }
};

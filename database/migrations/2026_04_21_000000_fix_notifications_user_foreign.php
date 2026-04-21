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
        Schema::table('notifications', function (Blueprint $table) {
            // drop existing foreign if present
            try {
                $table->dropForeign(['user_id']);
            } catch (\Exception $e) {
                // ignore if foreign key does not exist
            }

            // ensure column type is unsigned big integer (nullable allowed)
            $table->unsignedBigInteger('user_id')->nullable()->change();

            // add foreign referencing accounts.id instead of users.id
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
        Schema::table('notifications', function (Blueprint $table) {
            try {
                $table->dropForeign(['user_id']);
            } catch (\Exception $e) {
            }

            // restore to reference users table
            $table->unsignedBigInteger('user_id')->nullable()->change();

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }
};

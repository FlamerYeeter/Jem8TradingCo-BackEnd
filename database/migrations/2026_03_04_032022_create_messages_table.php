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
        if (!Schema::hasTable('messages')) {    
        Schema::create('messages', function (Blueprint $table) {
            $table->id('message_id');
            $table->unsignedBigInteger('chatroom_id');
            $table->string('messages', 225);
            $table->string('status', 255)->default('sent');
            $table->unsignedBigInteger('cart_id')->nullable();
            $table->timestamps();

            $table->foreign('chatroom_id')
                  ->references('chatroom_id')
                  ->on('create_live_chats_table')
                  ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
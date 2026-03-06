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
<<<<<<< HEAD:database/migrations/2026_03_04_032022_create_messages_table.php
                  ->references('chatroom_id')
                  ->on('live_chats')
                  ->onDelete('cascade');
=======
                ->references('chatroom_id')
                ->on('live_chats')
                ->onDelete('cascade');
>>>>>>> 56c9bc192dea37e0c9c945ceb4630dfbc85b88ed:database/migrations/2026_03_06_098022_create_messages_table.php
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
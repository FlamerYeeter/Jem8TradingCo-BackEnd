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
        Schema::create('attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('chatroom_id')->nullable()->index();
            $table->unsignedBigInteger('message_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('filename');
            $table->string('stored_name')->unique();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->string('processing_status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->increments('notification_id');
            $table->unsignedBigInteger('user_id');
            $table->string('type', 100)->nullable();
            $table->string('title', 255)->nullable();
            $table->text('message')->nullable();
            $table->string('reference_type', 100)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            $table->timestamp('read_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
}

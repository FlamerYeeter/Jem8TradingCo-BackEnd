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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id('message_id');
            $table->unsignedBigInteger('sender')->nullable();
            $table->string('first_name', 255);
            $table->string('last_name', 255);
            $table->string('phone_number', 50);
            $table->string('email', 50);
            $table->text('message');
            $table->enum('status', ['pending', 'read', 'replied'])->default('pending');
            $table->timestamps();

            $table->foreign('sender')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
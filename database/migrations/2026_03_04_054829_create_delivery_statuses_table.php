<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        
            Schema::create('delivery_status', function (Blueprint $table) {
                $table->id('delivery_id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('checkout_id');
                $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled']);
                $table->string('location', 255);
                $table->string('remarks', 255);
                $table->timestamps();

                $table->foreign('user_id')
                      ->references('id')
                      ->on('users')
                      ->onDelete('cascade');

                $table->foreign('checkout_id')
                      ->references('checkout_id')
                      ->on('checkouts')
                      ->onDelete('cascade');
            });
        
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_status');
    }
};
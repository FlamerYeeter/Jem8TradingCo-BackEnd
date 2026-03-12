<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('deliveries')) {
            Schema::create('deliveries', function (Blueprint $table) {
                $table->id('delivery_id');
                $table->unsignedBigInteger('checkout_id');
                $table->enum('status', ['processing', 'ready', 'on_the_way', 'delivered'])
                      ->default('processing');
                $table->unsignedBigInteger('driver_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('checkout_id')
                      ->references('checkout_id')
                      ->on('checkouts')
                      ->onDelete('cascade');

                // Optional: if you have drivers table
                // $table->foreign('driver_id')
                //       ->references('id')
                //       ->on('drivers')
                //       ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
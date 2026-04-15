<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('checkout_id');
            $table->unsignedBigInteger('product_id');

            $table->integer('quantity');
            $table->double('price');

            $table->timestamps();

            // ✅ relationships
            $table->foreign('checkout_id')
                ->references('checkout_id')
                ->on('checkouts')
                ->onDelete('cascade');

            $table->foreign('product_id')
                ->references('product_id')
                ->on('products')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_items');
    }
};
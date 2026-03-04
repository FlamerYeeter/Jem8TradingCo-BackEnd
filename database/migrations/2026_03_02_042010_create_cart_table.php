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
        if (!Schema::hasTable('cart')) {
        Schema::create('cart', function (Blueprint $table) {
            $table->id('cart_id'); // Primary Key
            $table->unsignedBigInteger('user_id'); // FK to users
            $table->unsignedBigInteger('product_id'); // FK to products
            $table->integer('quantity')->default(1);
            $table->decimal('total', 10, 2);
            $table->string('status')->default('pending'); // pending, completed
            $table->timestamps();

            // foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('cascade');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart');
    }
};

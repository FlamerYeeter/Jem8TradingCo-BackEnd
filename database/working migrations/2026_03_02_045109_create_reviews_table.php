<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reviews')) { 
        Schema::create('reviews', function (Blueprint $table) {
            $table->id('review_id'); // Primary Key
            $table->unsignedBigInteger('product_id'); // FK to products
            $table->unsignedBigInteger('user_id'); // FK to users
            $table->tinyInteger('rating'); // e.g., 1-5 stars
            $table->text('review_text');
            $table->string('status')->default('pending'); // pending/approved/rejected
            $table->timestamps();

            // foreign keys
            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
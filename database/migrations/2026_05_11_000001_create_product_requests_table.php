<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->enum('status', ['pending','found','not-available'])->default('pending');
            $table->timestamps();
            $table->engine = 'InnoDB';

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_requests');
    }
};

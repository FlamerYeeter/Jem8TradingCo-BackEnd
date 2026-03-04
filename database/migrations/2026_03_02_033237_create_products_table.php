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
        if (!Schema::hasTable('products')) {
        Schema::create('products', function (Blueprint $table) {
            $table->id('product_id'); // BIGINT UNSIGNED PK
            $table->string('product_name');
            $table->unsignedBigInteger('category_id'); // BIGINT UNSIGNED FK
            $table->integer('product_stocks')->default(0);
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->boolean('isSale')->default(false);
            $table->timestamps();
            $table->engine = 'InnoDB';

            $table->foreign('category_id')
                ->references('category_id') // exact name from categories
                ->on('categories')
                ->onDelete('cascade');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

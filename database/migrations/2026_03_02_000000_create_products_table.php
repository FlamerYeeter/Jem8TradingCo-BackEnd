<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('product_id');
            $table->string('product_name', 255);
            $table->unsignedInteger('category_id')->nullable();
            $table->integer('product_stocks')->default(0);
            $table->string('description', 255)->nullable();
            $table->double('price', 10, 2)->default(0);
            $table->boolean('isSale')->default(false);
            $table->double('reviews_id')->nullable();
            $table->string('img', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}

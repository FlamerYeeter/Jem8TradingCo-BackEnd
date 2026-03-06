<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id('category_id'); // BIGINT UNSIGNED PRIMARY KEY
            $table->string('category_name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->engine = 'InnoDB'; // make sure it's InnoDB
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
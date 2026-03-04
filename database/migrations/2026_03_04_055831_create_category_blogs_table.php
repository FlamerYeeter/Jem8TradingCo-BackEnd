<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        
            Schema::create('category_blog', function (Blueprint $table) {
                $table->id('category_blog_id');
                $table->string('category_name', 255);
                $table->string('description', 255);
                $table->string('img', 255);
                $table->timestamps();
            });
        
    }

    public function down(): void
    {
        Schema::dropIfExists('category_blog');
    }
};
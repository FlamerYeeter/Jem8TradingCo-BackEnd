<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        if (!Schema::hasTable('blog')) {
            Schema::create('blog', function (Blueprint $table) {
                $table->id('blog_id');
                $table->unsignedBigInteger('category_blog_id');
                $table->string('blog_title', 255);
                $table->text('blog_text');
                $table->string('featured_image', 255)->nullable();
                $table->text('images')->nullable();
                $table->enum('status', ['draft', 'published', 'archived']);
                $table->dateTime('update_at')->nullable();
                $table->string('updated_by', 100)->nullable();
                $table->timestamps();

                $table->foreign('category_blog_id')
                      ->references('category_blog_id')
                      ->on('category_blog')
                      ->onDelete('cascade');
            });
        }
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('blog');
    }
};
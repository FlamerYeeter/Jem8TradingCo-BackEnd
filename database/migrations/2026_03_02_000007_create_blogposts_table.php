<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBlogpostsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blogposts', function (Blueprint $table) {
            $table->increments('blog_id');
            $table->unsignedInteger('category_blog_id')->nullable();
            $table->string('blog_title', 255);
            $table->text('blog_text')->nullable();
            $table->string('featured_image', 255)->nullable();
            $table->text('images')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamps();
            $table->string('updated_by', 100)->nullable();

            $table->foreign('category_blog_id')->references('category_blog_id')->on('category_blogs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blogposts');
    }
}

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
        if (!Schema::hasTable('abouts')) {
        Schema::create('abouts', function (Blueprint $table) {
            $table->id('about_id');
            $table->string('mission', 255);
            $table->string('vission', 255);
            $table->unsignedBigInteger('leadership_id');
            $table->string('about_desc', 255);
            $table->timestamps();

            $table->foreign('leadership_id')
                  ->references('leadership_id')
                  ->on('admin_leaderships')
                  ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abouts');
    }
};
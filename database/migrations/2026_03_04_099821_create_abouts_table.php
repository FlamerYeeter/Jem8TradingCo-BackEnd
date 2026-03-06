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
        Schema::create('abouts', function (Blueprint $table) {
            $table->id('about_id');
            $table->string('mission', 255);
            $table->string('vission', 255);
            $table->foreignId('leadership_id')
                ->constrained('admin_leaderships', 'leadership_id')
                ->onDelete('cascade');
            $table->string('about_desc', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abouts');
    }
};
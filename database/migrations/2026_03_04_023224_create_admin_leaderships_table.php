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
        if (!Schema::hasTable('admin_leaderships')) {
            Schema::create('admin_leaderships', function (Blueprint $table) {
                $table->id('leadership_id');
                $table->string('name', 255);
                $table->string('position', 255);
                $table->boolean('status')->default(true);
                $table->string('leadership_img')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_leaderships');
    }
};
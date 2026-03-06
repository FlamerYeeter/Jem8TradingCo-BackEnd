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
                $table->unsignedBigInteger('user_id');
                $table->string('position', 255);
                $table->boolean('status')->default(true);
                $table->string('leadership_img')->nullable();
                $table->timestamps();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
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
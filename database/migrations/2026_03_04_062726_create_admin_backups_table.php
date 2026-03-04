<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        
            Schema::create('admin_backup', function (Blueprint $table) {
                $table->id('backup_id');
                $table->integer('backup_type');
                $table->bigInteger('backup_size');
                $table->enum('status', ['pending', 'completed', 'failed']);
                $table->timestamps();
            });
        
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_backup');
    }
};
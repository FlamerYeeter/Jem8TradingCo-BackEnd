<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        
        Schema::create('delivery_status', function (Blueprint $table) {
            $table->id('delivery_id');
            $table->foreignId('user_id')->constrained('accounts')->onDelete('cascade');
            $table->foreignId('checkout_id')->constrained('checkouts', 'checkout_id')->onDelete('cascade');
            $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled']);
            $table->string('location', 255);
            $table->string('remarks', 255);
            $table->timestamps();
        });
        
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_status');
    }
};
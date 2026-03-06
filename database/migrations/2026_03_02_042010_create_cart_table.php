<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cart')) {
            Schema::create('cart', function (Blueprint $table) {
                $table->id(); // PK 'id' (unsigned BIGINT)
                $table->foreignId('user_id')->constrained('accounts')->onDelete('cascade');
                $table->foreignId('product_id')->constrained('products', 'product_id')->onDelete('cascade');
                $table->integer('quantity')->default(1);
                $table->decimal('total', 10, 2);
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cart');
    }
};
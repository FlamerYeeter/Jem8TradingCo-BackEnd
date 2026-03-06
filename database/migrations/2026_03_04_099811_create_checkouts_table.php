<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkouts', function (Blueprint $table) {
            $table->id(); // PK 'id'

            $table->foreignId('user_id')->constrained('accounts')->onDelete('cascade');

            // Explicitly define cart_id to match cart.id
            $table->unsignedBigInteger('cart_id');
            $table->foreign('cart_id')->references('id')->on('cart')->onDelete('cascade');

            $table->foreignId('discount_id')->nullable()->constrained('discounts')->nullOnDelete();

            $table->string('payment_method', 255);
            $table->string('payment_reference', 255)->nullable();
            $table->double('shipping_fee')->default(0);
            $table->double('paid_amount')->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->text('special_instructions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkouts');
    }
};
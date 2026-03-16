<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function Symfony\Component\String\s;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::disableForeignKeyConstraints();
        if (!Schema::hasTable('checkouts')) {
        Schema::create('checkouts', function (Blueprint $table) {
            $table->id('checkout_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('discount_id')->nullable();
            $table->string('payment_method', 255);
            $table->string('payment_reference', 255)->nullable();
            $table->double('shipping_fee')->default(0);
            $table->double('paid_amount')->default(0);
            $table->double('paid_at')->nullable();
            $table->text('special_instructions')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('cart_id')
                ->references('cart_id')
                ->on('cart')
                ->onDelete('cascade');

            $table->foreign('discount_id')
                  ->references('discount_id')
                  ->on('discounts')
                  ->onDelete('set null');
        });
        }   
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkouts');
    }
};
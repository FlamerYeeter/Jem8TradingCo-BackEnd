<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        if (!Schema::hasTable('checkouts')) {

            Schema::create('checkouts', function (Blueprint $table) {

<<<<<<< HEAD
            $table->foreign('cart_id')
                ->references('cart_id')
                ->on('cart')
                ->onDelete('cascade');
=======
                $table->id('checkout_id');

                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('cart_id')->nullable();
                $table->unsignedBigInteger('discount_id')->nullable();

                $table->string('payment_method',255);
                $table->json('payment_details')->nullable();

                $table->double('shipping_fee')->default(0);
                $table->double('paid_amount')->default(0);

                $table->timestamp('paid_at')->nullable();

                $table->text('special_instructions')->nullable();

                $table->timestamps();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('accounts')
                    ->onDelete('cascade');

                $table->foreign('cart_id')
                    ->references('cart_id')
                    ->on('carts')
                    ->onDelete('cascade');

                $table->foreign('discount_id')
                    ->references('discount_id')
                    ->on('discounts')
                    ->onDelete('set null');
            });
        }
>>>>>>> c681468574960909dc386ae5e8fcd8f75250f260

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('checkouts');
    }
};
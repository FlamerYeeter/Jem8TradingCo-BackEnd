<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCheckoutTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('checkout')) {
            Schema::create('checkout', function (Blueprint $table) {
                $table->increments('checkout_id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedInteger('cart_id')->nullable();
                $table->unsignedInteger('discount_id')->nullable();
                $table->string('payment_method', 255)->nullable();
                $table->string('payment_reference', 255)->nullable();
                $table->double('shipping_fee', 10, 2)->default(0);
                $table->double('paid_amount', 10, 2)->default(0);
                $table->timestamp('paid_at')->nullable();
                $table->text('special_instructions')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('cart_id')->references('cart_id')->on('addtocart')->onDelete('set null');
                $table->foreign('discount_id')->references('discount_id')->on('discount')->onDelete('set null');
            });
        } else {
            // If the table already exists, ensure cart_id is nullable to support onDelete('set null')
            if (!Schema::hasColumn('checkout', 'cart_id')) {
                Schema::table('checkout', function (Blueprint $table) {
                    $table->unsignedInteger('cart_id')->nullable();
                });
            } else {
                // If cart_id already exists, do not attempt to alter it here to avoid requiring doctrine/dbal.
                // If you need to make it nullable on an existing table, run a manual ALTER TABLE or install doctrine/dbal.
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkout');
    }
}

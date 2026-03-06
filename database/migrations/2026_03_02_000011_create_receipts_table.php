<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->increments('receipt_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('checkout_id')->nullable();
            $table->string('receipt_number', 255)->unique();
            $table->string('payment_method', 255)->nullable();
            $table->string('payment_reference', 255)->nullable();
            $table->double('paid_amount', 10, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('checkout_id')->references('checkout_id')->on('checkout')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
}

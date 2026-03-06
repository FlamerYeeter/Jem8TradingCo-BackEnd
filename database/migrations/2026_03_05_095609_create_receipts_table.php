<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('receipts')) {
            Schema::create('receipts', function (Blueprint $table) {
                $table->id('receipt_id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('checkout_id');
                $table->string('receipt_number', 255)->unique();
                $table->enum('payment_method', ['cash', 'credit_card', 'debit_card', 'gcash', 'maya', 'bank_transfer']);
                $table->string('payment_reference', 255);
                $table->double('paid_amount');
                $table->dateTime('paid_at');
                $table->timestamps();

                $table->foreign('user_id')
                      ->references('id')
                      ->on('users')
                      ->onDelete('cascade');

                $table->foreign('checkout_id')
                      ->references('checkout_id')
                      ->on('checkouts')
                      ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
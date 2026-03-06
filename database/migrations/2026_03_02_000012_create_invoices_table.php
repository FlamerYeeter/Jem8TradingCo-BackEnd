<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->increments('invoice_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('checkout_id')->nullable();
            $table->unsignedInteger('receipt_id')->nullable();
            $table->string('invoice_number', 255)->unique();
            $table->string('billing_address', 255)->nullable();
            $table->double('tax_amount', 10, 2)->default(0);
            $table->double('total_amount', 10, 2)->default(0);
            $table->enum('status', ['unpaid', 'paid'])->default('unpaid');
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('checkout_id')->references('checkout_id')->on('checkout')->onDelete('set null');
            $table->foreign('receipt_id')->references('receipt_id')->on('receipts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
}

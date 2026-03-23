<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('receipts')) {
            DB::statement("ALTER TABLE `receipts` MODIFY `payment_method` ENUM('cash','credit_card','debit_card','gcash','maya','bank_transfer','cod','cash_on_delivery','check') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('receipts')) {
            DB::statement("ALTER TABLE `receipts` MODIFY `payment_method` ENUM('cash','credit_card','debit_card','gcash','maya','bank_transfer','cod','cash_on_delivery') NOT NULL");
        }
    }
};

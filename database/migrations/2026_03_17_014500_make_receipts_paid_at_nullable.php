<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('receipts')) {
            DB::statement('ALTER TABLE `receipts` MODIFY `paid_at` DATETIME NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('receipts')) {
            DB::statement('ALTER TABLE `receipts` MODIFY `paid_at` DATETIME NOT NULL');
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('receipts')) {
            // Attempt to drop existing FK by name (ignore errors if it doesn't exist)
            try {
                DB::statement('ALTER TABLE receipts DROP FOREIGN KEY receipts_checkout_id_foreign');
            } catch (\Exception $e) {
                // ignore
            }

            // Modify column type to match checkouts.checkout_id
            DB::statement('ALTER TABLE receipts MODIFY checkout_id bigint(20) unsigned NULL');

            // Null out any receipts.checkout_id values that don't exist in checkouts
            DB::statement('UPDATE receipts r LEFT JOIN checkouts c ON r.checkout_id = c.checkout_id SET r.checkout_id = NULL WHERE r.checkout_id IS NOT NULL AND c.checkout_id IS NULL');

            // Add new FK pointing to `checkouts` table
            Schema::table('receipts', function (Blueprint $table) {
                $table->foreign('checkout_id')
                      ->references('checkout_id')
                      ->on('checkouts')
                      ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('receipts')) {
            Schema::table('receipts', function (Blueprint $table) {
                // Drop FK referencing checkouts
                $table->dropForeign(['checkout_id']);
                // Restore original type back to int if needed
            });
            DB::statement('ALTER TABLE receipts MODIFY checkout_id int(10) unsigned NULL');
        }
    }
};

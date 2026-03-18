<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw statement to avoid requiring doctrine/dbal for change()
        if (DB::getSchemaBuilder()->hasColumn('user_addresses', 'address')) {
            DB::statement('ALTER TABLE `user_addresses` MODIFY `address` TEXT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getSchemaBuilder()->hasColumn('user_addresses', 'address')) {
            DB::statement('ALTER TABLE `user_addresses` MODIFY `address` TEXT NOT NULL');
        }
    }
};

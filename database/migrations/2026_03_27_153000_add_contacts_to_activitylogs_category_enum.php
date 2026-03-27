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
        // Add 'contacts' to the activity_logs.category enum to avoid truncation warnings
        DB::statement("ALTER TABLE `activity_logs` MODIFY `category` ENUM('orders','stock','account','blogs','payments','backups','other','contacts') NOT NULL DEFAULT 'other'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous enum values (removes 'contacts')
        DB::statement("ALTER TABLE `activity_logs` MODIFY `category` ENUM('orders','stock','account','blogs','payments','backups','other') NOT NULL DEFAULT 'other'");
    }
};

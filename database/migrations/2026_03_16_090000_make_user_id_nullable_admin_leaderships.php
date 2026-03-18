<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('admin_leaderships') && Schema::hasColumn('admin_leaderships', 'user_id')) {
            // Modify column to allow NULL. Use raw SQL to avoid requiring doctrine/dbal.
            DB::statement('ALTER TABLE `admin_leaderships` MODIFY `user_id` BIGINT UNSIGNED NULL;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('admin_leaderships') && Schema::hasColumn('admin_leaderships', 'user_id')) {
            DB::statement('ALTER TABLE `admin_leaderships` MODIFY `user_id` BIGINT UNSIGNED NOT NULL;');
        }
    }
};

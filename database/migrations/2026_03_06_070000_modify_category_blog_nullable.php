<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Use raw statements to avoid requiring doctrine/dbal for simple column changes
        \DB::statement("ALTER TABLE `category_blog` MODIFY `description` VARCHAR(255) NULL");
        \DB::statement("ALTER TABLE `category_blog` MODIFY `img` VARCHAR(255) NULL");
    }

    public function down(): void
    {
        // Revert to NOT NULL with empty string default to match original strictness
        \DB::statement("ALTER TABLE `category_blog` MODIFY `description` VARCHAR(255) NOT NULL DEFAULT ''");
        \DB::statement("ALTER TABLE `category_blog` MODIFY `img` VARCHAR(255) NOT NULL DEFAULT ''");
    }
};

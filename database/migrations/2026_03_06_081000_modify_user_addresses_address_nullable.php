<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // public function up(): void
    // {
    //     // make the old free-form `address` column nullable to avoid insert errors
    //     DB::statement('ALTER TABLE `user_addresses` MODIFY `address` TEXT NULL');
    // }

    // public function down(): void
    // {
    //     DB::statement('ALTER TABLE `user_addresses` MODIFY `address` TEXT NOT NULL');
    // }
};

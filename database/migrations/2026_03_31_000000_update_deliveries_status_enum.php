<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration updates the `status` enum on the `deliveries` table
     * to a canonical set and converts existing `shipped` values to `on_the_way`.
     */
    public function up()
    {
        // Add canonical enum values: pending, processing, ready, on_the_way, delivered, cancelled
        DB::statement("ALTER TABLE `deliveries` MODIFY `status` ENUM('pending','processing','ready','on_the_way','delivered','cancelled') NOT NULL DEFAULT 'processing'");

        // Convert legacy 'shipped' values to 'on_the_way'
        DB::table('deliveries')->where('status', 'shipped')->update(['status' => 'on_the_way']);
    }

    /**
     * Reverse the migrations.
     *
     * Revert enum to previous form that included 'shipped' and convert back.
     */
    public function down()
    {
        // Convert 'on_the_way' back to 'shipped' for rollback
        DB::table('deliveries')->where('status', 'on_the_way')->update(['status' => 'shipped']);

        DB::statement("ALTER TABLE `deliveries` MODIFY `status` ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'processing'");
    }
};

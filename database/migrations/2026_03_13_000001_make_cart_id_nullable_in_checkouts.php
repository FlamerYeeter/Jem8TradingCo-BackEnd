<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getSchemaBuilder()->hasTable('checkouts')) {
            DB::statement("ALTER TABLE checkouts MODIFY cart_id bigint(20) unsigned NULL;");
        }
    }

    public function down(): void
    {
        if (DB::getSchemaBuilder()->hasTable('checkouts')) {
            DB::statement("ALTER TABLE checkouts MODIFY cart_id bigint(20) unsigned NOT NULL;");
        }
    }
};

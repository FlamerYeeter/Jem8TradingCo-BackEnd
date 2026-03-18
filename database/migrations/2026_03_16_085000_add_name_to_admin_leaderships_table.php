<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('admin_leaderships') && !Schema::hasColumn('admin_leaderships', 'name')) {
            Schema::table('admin_leaderships', function (Blueprint $table) {
                $table->string('name', 255)->nullable()->after('leadership_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('admin_leaderships') && Schema::hasColumn('admin_leaderships', 'name')) {
            Schema::table('admin_leaderships', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }
};

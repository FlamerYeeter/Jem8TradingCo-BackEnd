<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_leaderships', function (Blueprint $table) {
            $table->string('team')->default('leadership')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('admin_leaderships', function (Blueprint $table) {
            $table->dropColumn('team');
        });
    }
};

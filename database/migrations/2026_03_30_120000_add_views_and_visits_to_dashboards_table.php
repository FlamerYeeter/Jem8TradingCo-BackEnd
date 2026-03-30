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
        if (!Schema::hasTable('dashboards')) {
            return;
        }

        if (!Schema::hasColumn('dashboards', 'views')) {
            Schema::table('dashboards', function (Blueprint $table) {
                $table->unsignedBigInteger('views')->default(0)->after('id');
            });
        }

        if (!Schema::hasColumn('dashboards', 'visits')) {
            Schema::table('dashboards', function (Blueprint $table) {
                $table->unsignedBigInteger('visits')->default(0)->after('views');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('dashboards')) {
            return;
        }

        if (Schema::hasColumn('dashboards', 'visits')) {
            Schema::table('dashboards', function (Blueprint $table) {
                $table->dropColumn('visits');
            });
        }

        if (Schema::hasColumn('dashboards', 'views')) {
            Schema::table('dashboards', function (Blueprint $table) {
                $table->dropColumn('views');
            });
        }
    }
};

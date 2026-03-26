<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('activity_logs', 'reference_table')) {
                $table->string('reference_table', 100)->nullable();
            }

            if (!Schema::hasColumn('activity_logs', 'reference_id')) {
                $table->unsignedBigInteger('reference_id')->nullable();
            }

            // Add index only if both columns now exist
            if (Schema::hasColumn('activity_logs', 'reference_table') &&
                Schema::hasColumn('activity_logs', 'reference_id')) {

                $indexes = collect(Schema::getConnection()
                    ->select("SHOW INDEX FROM activity_logs"))
                    ->pluck('Key_name')
                    ->toArray();

                if (!in_array('activity_logs_reference_idx', $indexes, true)) {
                    $table->index(['reference_table', 'reference_id'], 'activity_logs_reference_idx');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $indexes = collect(Schema::getConnection()
                ->select("SHOW INDEX FROM activity_logs"))
                ->pluck('Key_name')
                ->toArray();

            if (in_array('activity_logs_reference_idx', $indexes, true)) {
                $table->dropIndex('activity_logs_reference_idx');
            }

            if (Schema::hasColumn('activity_logs', 'reference_id')) {
                $table->dropColumn('reference_id');
            }

            if (Schema::hasColumn('activity_logs', 'reference_table')) {
                $table->dropColumn('reference_table');
            }
        });
    }
};

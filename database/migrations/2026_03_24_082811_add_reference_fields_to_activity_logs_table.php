<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add columns only if they don't already exist (safe if migration partially applied)
        if (!Schema::hasColumn('activity_logs', 'reference_table') || !Schema::hasColumn('activity_logs', 'reference_id')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('activity_logs', 'reference_table')) {
                    $table->string('reference_table', 100)->nullable()->after('description');
                }

                if (!Schema::hasColumn('activity_logs', 'reference_id')) {
                    $table->unsignedBigInteger('reference_id')->nullable()->after('reference_table');
                }

                // Add index if it doesn't exist (guard by checking index name)
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = array_map(fn($idx) => $idx->getName(), $sm->listTableIndexes('activity_logs'));
                if (!in_array('activity_logs_reference_idx', $indexes, true)) {
                    $table->index(['reference_table', 'reference_id'], 'activity_logs_reference_idx');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // Drop index if exists then drop columns if they exist
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = array_map(fn($idx) => $idx->getName(), $sm->listTableIndexes('activity_logs'));
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
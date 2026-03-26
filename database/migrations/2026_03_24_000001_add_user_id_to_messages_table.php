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
        if (Schema::hasTable('messages') && ! Schema::hasColumn('messages', 'user_id')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('chatroom_id');
                $table->index('user_id');

                // Reference accounts table (if present). Use set null on delete to keep message history.
                $table->foreign('user_id')
                    ->references('id')
                    ->on('accounts')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('messages') && Schema::hasColumn('messages', 'user_id')) {
            Schema::table('messages', function (Blueprint $table) {
                // drop foreign and column safely
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $doctrineTable = $sm->listTableDetails(Schema::getConnection()->getTablePrefix() . 'messages');
                if ($doctrineTable->hasForeignKey('messages_user_id_foreign')) {
                    $table->dropForeign('messages_user_id_foreign');
                }
                $table->dropIndex(['user_id']);
                $table->dropColumn('user_id');
            });
        }
    }
};

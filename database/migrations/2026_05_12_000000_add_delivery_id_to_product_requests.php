<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_requests')) {
            Schema::table('product_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('product_requests', 'delivery_id')) {
                    $table->unsignedBigInteger('delivery_id')->nullable()->after('image_path');
                    $table->foreign('delivery_id')->references('delivery_id')->on('deliveries')->onDelete('set null');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_requests')) {
            Schema::table('product_requests', function (Blueprint $table) {
                if (Schema::hasColumn('product_requests', 'delivery_id')) {
                    $table->dropForeign(['delivery_id']);
                    $table->dropColumn('delivery_id');
                }
            });
        }
    }
};

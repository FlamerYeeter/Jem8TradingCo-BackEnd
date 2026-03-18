<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cart') && !Schema::hasColumn('cart', 'is_checkout')) {
            Schema::table('cart', function (Blueprint $table) {
                $table->boolean('is_checkout')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cart') && Schema::hasColumn('cart', 'is_checkout')) {
            Schema::table('cart', function (Blueprint $table) {
                $table->dropColumn('is_checkout');
            });
        }
    }
};

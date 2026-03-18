<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('checkouts') && !Schema::hasColumn('checkouts', 'payment_details')) {
            Schema::table('checkouts', function (Blueprint $table) {
                $table->json('payment_details')->nullable()->after('payment_method');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('checkouts') && Schema::hasColumn('checkouts', 'payment_details')) {
            Schema::table('checkouts', function (Blueprint $table) {
                $table->dropColumn('payment_details');
            });
        }
    }
};

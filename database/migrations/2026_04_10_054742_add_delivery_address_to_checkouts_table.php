<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Schema::table('checkouts', function (Blueprint $table) {
        //     $table->json('delivery_address')->nullable()->after('payment_details');
        // });
    }

    public function down(): void
    {
        // Schema::table('checkouts', function (Blueprint $table) {
        //     $table->dropColumn('delivery_address');
        // });
    }
};

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
    Schema::table('accounts', function (Blueprint $table) {
        $table->string('tin_number', 12)->nullable()->after('business_type');
    });
}

public function down(): void
{
    Schema::table('accounts', function (Blueprint $table) {
        $table->dropColumn('tin_number');
    });
}
};

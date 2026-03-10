<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->enum('type', ['personal', 'company'])->default('personal')->after('user_id');

            // address details (keep existing `address` column for compatibility)
            $table->string('street')->nullable()->after('company_email');
            $table->string('barangay')->nullable()->after('street');
            $table->string('city')->nullable()->after('barangay');
            $table->string('province')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('province');
            $table->string('country')->nullable()->default('Philippines')->after('postal_code');
        });

        // copy existing free-form `address` into `street` for current rows
        DB::table('user_addresses')->whereNotNull('address')->update([
            'street' => DB::raw('address'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $table->dropColumn(['type', 'street', 'barangay', 'city', 'province', 'postal_code', 'country']);
        });
    }
};

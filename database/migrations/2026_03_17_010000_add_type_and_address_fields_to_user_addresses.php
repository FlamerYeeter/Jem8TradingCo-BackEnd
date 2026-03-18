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
            if (!Schema::hasColumn('user_addresses', 'type')) {
                $table->enum('type', ['personal', 'company'])->default('personal')->after('user_id');
            }

            if (!Schema::hasColumn('user_addresses', 'company_name')) {
                $table->string('company_name')->nullable()->after('type');
            }
            if (!Schema::hasColumn('user_addresses', 'company_role')) {
                $table->string('company_role')->nullable()->after('company_name');
            }
            if (!Schema::hasColumn('user_addresses', 'company_number')) {
                $table->string('company_number')->nullable()->after('company_role');
            }
            if (!Schema::hasColumn('user_addresses', 'company_email')) {
                $table->string('company_email')->nullable()->after('company_number');
            }
            if (!Schema::hasColumn('user_addresses', 'street')) {
                $table->string('street')->nullable()->after('company_email');
            }
            if (!Schema::hasColumn('user_addresses', 'barangay')) {
                $table->string('barangay')->nullable()->after('street');
            }
            if (!Schema::hasColumn('user_addresses', 'city')) {
                $table->string('city')->nullable()->after('barangay');
            }
            if (!Schema::hasColumn('user_addresses', 'province')) {
                $table->string('province')->nullable()->after('city');
            }
            if (!Schema::hasColumn('user_addresses', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('province');
            }
            if (!Schema::hasColumn('user_addresses', 'country')) {
                $table->string('country')->nullable()->default('Philippines')->after('postal_code');
            }
        });

        // If older free-form `address` column exists, copy it into `street` for existing rows
        if (Schema::hasColumn('user_addresses', 'address') && Schema::hasColumn('user_addresses', 'street')) {
            DB::table('user_addresses')->whereNotNull('address')->update([
                'street' => DB::raw('address'),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            $drop = [];
            foreach (['type','company_name','company_role','company_number','company_email','street','barangay','city','province','postal_code','country'] as $col) {
                if (Schema::hasColumn('user_addresses', $col)) {
                    $drop[] = $col;
                }
            }

            if (count($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};

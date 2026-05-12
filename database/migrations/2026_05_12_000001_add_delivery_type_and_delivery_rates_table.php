<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // add delivery_type column to checkouts
        if (Schema::hasTable('checkouts') && ! Schema::hasColumn('checkouts', 'delivery_type')) {
            Schema::table('checkouts', function (Blueprint $table) {
                $table->string('delivery_type')->nullable()->after('delivery_country')->default('inhouse');
            });
        }

        // create delivery_rates table
        if (! Schema::hasTable('delivery_rates')) {
            Schema::create('delivery_rates', function (Blueprint $table) {
                $table->id();
                $table->string('province')->nullable();
                $table->string('city')->nullable();
                $table->string('barangay')->nullable();
                $table->double('fee')->default(0);
                $table->text('note')->nullable();
                $table->timestamps();

                $table->index(['province', 'city', 'barangay']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('checkouts') && Schema::hasColumn('checkouts', 'delivery_type')) {
            Schema::table('checkouts', function (Blueprint $table) {
                $table->dropColumn('delivery_type');
            });
        }

        Schema::dropIfExists('delivery_rates');
    }
};

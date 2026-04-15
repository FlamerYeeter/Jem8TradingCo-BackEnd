<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('checkouts', function (Blueprint $table) {

            // ❌ remove cart_id (old wrong design)
            if (Schema::hasColumn('checkouts', 'cart_id')) {
                $table->dropForeign(['cart_id']);
                $table->dropColumn('cart_id');
            }

            // ✅ add delivery_address (JSON)
            if (!Schema::hasColumn('checkouts', 'delivery_address')) {
                $table->json('delivery_address')->nullable()->after('payment_details');
            }
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('checkouts', function (Blueprint $table) {

            // rollback delivery_address
            if (Schema::hasColumn('checkouts', 'delivery_address')) {
                $table->dropColumn('delivery_address');
            }

            // restore cart_id (if rollback)
            $table->unsignedBigInteger('cart_id')->nullable();

            $table->foreign('cart_id')
                ->references('cart_id')
                ->on('carts')
                ->onDelete('cascade');
        });

        Schema::enableForeignKeyConstraints();
    }
};
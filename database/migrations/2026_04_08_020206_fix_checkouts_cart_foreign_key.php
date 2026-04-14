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
        Schema::disableForeignKeyConstraints();

        Schema::table('checkouts', function (Blueprint $table) {
            $table->dropForeign(['cart_id']);

            $table->foreign('cart_id')
                ->references('cart_id')
                ->on('cart')          // ✅ correct table name
                ->onDelete('cascade');
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('checkouts', function (Blueprint $table) {
            $table->dropForeign(['cart_id']);

            $table->foreign('cart_id')
                ->references('cart_id')
                ->on('carts')
                ->onDelete('cascade');
        });

        Schema::enableForeignKeyConstraints();
    }
};

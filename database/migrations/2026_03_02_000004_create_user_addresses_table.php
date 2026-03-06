<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAddressesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->increments('user_address_id');
            $table->unsignedBigInteger('user_id');
            $table->string('company_name', 255)->nullable();
            $table->string('company_role', 255)->nullable();
            $table->string('company_number', 20)->nullable();
            $table->string('company_email', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('status', 50)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
}

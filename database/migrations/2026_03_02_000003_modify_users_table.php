<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('firstname', 255)->nullable()->after('id');
            $table->string('lastname', 255)->nullable()->after('firstname');
            $table->string('phone_number', 20)->nullable()->after('lastname');
            $table->string('role', 50)->nullable()->after('password');
            $table->string('permission', 100)->nullable()->after('role');
            $table->string('email_verification_code', 255)->nullable()->after('permission');
            $table->timestamp('email_verification_expires_at')->nullable()->after('email_verification_code');
            $table->string('password_reset_code', 100)->nullable()->after('email_verification_expires_at');
            $table->timestamp('password_reset_expires_at')->nullable()->after('password_reset_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'firstname',
                'lastname',
                'phone_number',
                'role',
                'permission',
                'email_verification_code',
                'email_verification_expires_at',
                'password_reset_code',
                'password_reset_expires_at',
            ]);
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('accounts', function (Blueprint $table) {
        $table->string('google_avatar')->nullable()->after('google_id');
    });
}

public function down()
{
    Schema::table('accounts', function (Blueprint $table) {
        $table->dropColumn('google_avatar');
    });
}
};

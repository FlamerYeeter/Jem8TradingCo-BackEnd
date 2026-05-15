<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDepartmentToAccountsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('accounts', 'department')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->string('department')->nullable()->after('role');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('accounts', 'department')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropColumn('department');
            });
        }
    }
}

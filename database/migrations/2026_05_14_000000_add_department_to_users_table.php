<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDepartmentToUsersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('users', 'department')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'role')) {
                    $table->string('department')->nullable()->after('role');
                } else {
                    $table->string('department')->nullable();
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'department')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('department');
            });
        }
    }
}

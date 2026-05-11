<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->enum('message_type', ['inquiry','sales','finance','marketing','report_bugs'])->default('inquiry')->after('message');
            $table->string('attachment_path')->nullable()->after('message_type');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['message_type', 'attachment_path']);
        });
    }
};

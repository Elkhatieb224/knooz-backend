<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_users', function (Blueprint $table) {
            $table->string('role')->nullable()->after('status');
            $table->unsignedBigInteger('created_by')->nullable()->after('legacy_user_id');
        });

        // Existing company users keep full access.
        DB::table('company_users')->whereNull('role')->update(['role' => 'owner']);

        Schema::table('company_users', function (Blueprint $table) {
            $table->string('role')->default('owner')->nullable(false)->change();
            $table->index('role');
            $table->foreign('created_by')->references('id')->on('company_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('company_users', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropIndex(['role']);
        });

        Schema::table('company_users', function (Blueprint $table) {
            $table->dropColumn(['role', 'created_by']);
        });
    }
};

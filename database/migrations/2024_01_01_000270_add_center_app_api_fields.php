<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('center_users', function (Blueprint $table) {
            $table->string('role')->default('manager')->after('status')->index();
        });

        Schema::table('centers', function (Blueprint $table) {
            $table->boolean('show_revenue')->nullable()->after('status');
        });

        DB::table('centers')->where('type', 'owned')->update(['show_revenue' => true]);
        DB::table('centers')->where('type', 'contracted')->update(['show_revenue' => false]);

        Schema::table('operations', function (Blueprint $table) {
            $table->foreignId('created_by_center_user_id')
                ->nullable()
                ->after('employee_id')
                ->constrained('center_users')
                ->nullOnDelete();
            $table->string('source')->nullable()->after('notes')->index();
        });

        Schema::table('complaints', function (Blueprint $table) {
            $table->foreignId('center_id')
                ->nullable()
                ->after('user_id')
                ->constrained('centers')
                ->nullOnDelete();
            $table->foreignId('operation_id')
                ->nullable()
                ->after('center_id')
                ->constrained('operations')
                ->nullOnDelete();
            $table->string('source')->nullable()->after('admin_notes')->index();
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operation_id');
            $table->dropConstrainedForeignId('center_id');
            $table->dropColumn('source');
        });

        Schema::table('operations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_center_user_id');
            $table->dropColumn('source');
        });

        Schema::table('centers', function (Blueprint $table) {
            $table->dropColumn('show_revenue');
        });

        Schema::table('center_users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};

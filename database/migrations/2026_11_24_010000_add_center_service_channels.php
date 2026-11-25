<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->boolean('offers_center_service')->default(true)->after('status');
            $table->boolean('offers_home_service')->default(true)->after('offers_center_service');
            $table->boolean('offers_emergency_service')->default(true)->after('offers_home_service');
        });

        if (! Schema::hasColumn('emergency_requests', 'center_id')) {
            Schema::table('emergency_requests', function (Blueprint $table) {
                $table->foreignId('center_id')->nullable()->after('vehicle_id')->constrained('centers')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('emergency_requests', 'center_id')) {
            Schema::table('emergency_requests', function (Blueprint $table) {
                $table->dropConstrainedForeignId('center_id');
            });
        }

        Schema::table('centers', function (Blueprint $table) {
            $table->dropColumn([
                'offers_center_service',
                'offers_home_service',
                'offers_emergency_service',
            ]);
        });
    }
};

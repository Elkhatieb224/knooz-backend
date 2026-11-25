<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('center_service', function (Blueprint $table) {
            $table->string('app_profit_mode', 16)->default('fixed')->after('app_profit');
        });
    }

    public function down(): void
    {
        Schema::table('center_service', function (Blueprint $table) {
            $table->dropColumn('app_profit_mode');
        });
    }
};

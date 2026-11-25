<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->decimal('product_app_profit', 10, 2)->nullable()->after('show_revenue');
            $table->string('product_app_profit_mode', 16)->default('fixed')->after('product_app_profit');
        });
    }

    public function down(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->dropColumn(['product_app_profit', 'product_app_profit_mode']);
        });
    }
};

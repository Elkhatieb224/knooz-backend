<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->foreignId('center_id')
                ->nullable()
                ->after('id')
                ->constrained('centers')
                ->nullOnDelete();
            $table->decimal('app_profit', 10, 2)->nullable()->after('price');
            $table->string('app_profit_mode', 16)->default('fixed')->after('app_profit');
            $table->index(['center_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropIndex(['center_id', 'is_active']);
            $table->dropConstrainedForeignId('center_id');
            $table->dropColumn(['app_profit', 'app_profit_mode']);
        });
    }
};

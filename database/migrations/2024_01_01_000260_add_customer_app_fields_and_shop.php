<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('city')->nullable()->after('phone');
            $table->unsignedInteger('loyalty_points')->default(0)->after('avatar');
            $table->boolean('notifications_enabled')->default(true)->after('loyalty_points');
            $table->string('gender', 16)->nullable()->after('name');
        });

        Schema::create('shop_products', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('slug')->unique();
            $table->string('category', 64)->index();
            $table->string('brand')->nullable()->index();
            $table->string('size')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('image_path')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['category', 'is_active', 'brand']);
        });

        Schema::create('service_request_metas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('request_kind', 32)->index();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_request_metas');
        Schema::dropIfExists('shop_products');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['city', 'loyalty_points', 'notifications_enabled', 'gender']);
        });
    }
};

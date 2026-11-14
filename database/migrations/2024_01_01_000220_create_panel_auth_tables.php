<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('password');
            $table->string('role')->index();
            $table->string('status')->default('active')->index();
            $table->string('theme')->default('light');
            $table->string('avatar')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedBigInteger('legacy_user_id')->nullable()->index();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('company_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('password');
            $table->string('status')->default('active')->index();
            $table->string('theme')->default('light');
            $table->string('avatar')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedBigInteger('legacy_user_id')->nullable()->index();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('center_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('centers')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('password');
            $table->string('status')->default('active')->index();
            $table->string('theme')->default('light');
            $table->string('avatar')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedBigInteger('legacy_user_id')->nullable()->index();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('centers', function (Blueprint $table) {
            $table->foreignId('manager_center_user_id')
                ->nullable()
                ->after('manager_id')
                ->constrained('center_users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_center_user_id');
        });

        Schema::dropIfExists('center_users');
        Schema::dropIfExists('company_users');
        Schema::dropIfExists('admin_users');
    }
};

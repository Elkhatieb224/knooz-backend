<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('notification_type', 64)->nullable()->after('type');
            $table->string('reference_type')->nullable()->after('notification_type');
            $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            $table->string('event_key', 191)->nullable()->unique()->after('reference_id');
            $table->timestamp('archived_at')->nullable()->after('read_at');

            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_recipient_read_idx');
            $table->index(['notifiable_type', 'notifiable_id', 'created_at'], 'notifications_recipient_created_idx');
            $table->index(['notifiable_type', 'notifiable_id', 'notification_type'], 'notifications_recipient_type_idx');
            $table->index(['reference_type', 'reference_id'], 'notifications_reference_idx');
        });

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('token', 512);
            $table->string('platform', 32)->nullable();
            $table->string('app', 32)->default('mobile');
            $table->string('device_id', 191)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['tokenable_type', 'tokenable_id', 'token'], 'device_tokens_owner_token_uq');
            $table->index(['tokenable_type', 'tokenable_id', 'app'], 'device_tokens_owner_app_idx');
        });

        Schema::create('panel_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token_hash', 64)->unique();
            $table->string('panel', 16);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['tokenable_type', 'tokenable_id'], 'panel_api_tokens_owner_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panel_api_tokens');
        Schema::dropIfExists('device_tokens');

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_recipient_read_idx');
            $table->dropIndex('notifications_recipient_created_idx');
            $table->dropIndex('notifications_recipient_type_idx');
            $table->dropIndex('notifications_reference_idx');
            $table->dropUnique(['event_key']);
            $table->dropColumn([
                'notification_type',
                'reference_type',
                'reference_id',
                'event_key',
                'archived_at',
            ]);
        });
    }
};

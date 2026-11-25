<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wallets')) {
            return;
        }

        // Keep the oldest wallet per owner and drop accidental duplicates.
        $duplicateUserIds = DB::table('wallets')
            ->select('user_id')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        foreach ($duplicateUserIds as $userId) {
            $keepId = DB::table('wallets')->where('user_id', $userId)->orderBy('id')->value('id');
            DB::table('wallets')->where('user_id', $userId)->where('id', '!=', $keepId)->delete();
        }

        $duplicateCompanyIds = DB::table('wallets')
            ->select('company_id')
            ->whereNotNull('company_id')
            ->groupBy('company_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('company_id');

        foreach ($duplicateCompanyIds as $companyId) {
            $keepId = DB::table('wallets')->where('company_id', $companyId)->orderBy('id')->value('id');
            DB::table('wallets')->where('company_id', $companyId)->where('id', '!=', $keepId)->delete();
        }

        Schema::table('wallets', function (Blueprint $table) {
            $table->unique('user_id', 'wallets_user_id_unique');
            $table->unique('company_id', 'wallets_company_id_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('wallets')) {
            return;
        }

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropUnique('wallets_user_id_unique');
            $table->dropUnique('wallets_company_id_unique');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('admin_users')) {
            return;
        }

        $now = now();
        $adminRoles = ['super_admin', 'operations_manager', 'accountant', 'support'];

        $adminRows = DB::table('users')
            ->whereIn('role', $adminRoles)
            ->whereNull('deleted_at')
            ->get();

        foreach ($adminRows as $row) {
            $exists = DB::table('admin_users')
                ->where(function ($q) use ($row) {
                    $q->where('email', $row->email)
                        ->orWhere('legacy_user_id', $row->id);
                })
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('admin_users')->insert([
                'name' => $row->name,
                'email' => $row->email,
                'phone' => $row->phone,
                'password' => $row->password,
                'role' => $row->role,
                'status' => $row->status ?? 'active',
                'theme' => $row->theme ?? 'light',
                'avatar' => $row->avatar,
                'last_login_at' => $row->last_login_at,
                'legacy_user_id' => $row->id,
                'remember_token' => $row->remember_token,
                'created_at' => $row->created_at ?? $now,
                'updated_at' => $row->updated_at ?? $now,
            ]);
        }

        $companyRows = DB::table('users')
            ->where('role', 'customer_company')
            ->whereNotNull('company_id')
            ->whereNull('deleted_at')
            ->get();

        foreach ($companyRows as $row) {
            $exists = DB::table('company_users')
                ->where(function ($q) use ($row) {
                    $q->where('email', $row->email)
                        ->orWhere('legacy_user_id', $row->id);
                })
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('company_users')->insert([
                'company_id' => $row->company_id,
                'name' => $row->name,
                'email' => $row->email,
                'phone' => $row->phone,
                'password' => $row->password,
                'status' => $row->status ?? 'active',
                'theme' => $row->theme ?? 'light',
                'avatar' => $row->avatar,
                'last_login_at' => $row->last_login_at,
                'legacy_user_id' => $row->id,
                'remember_token' => $row->remember_token,
                'created_at' => $row->created_at ?? $now,
                'updated_at' => $row->updated_at ?? $now,
            ]);
        }

        $centerRows = DB::table('users')
            ->where('role', 'center_manager')
            ->whereNotNull('center_id')
            ->whereNull('deleted_at')
            ->get();

        $legacyToCenterUser = [];

        foreach ($centerRows as $row) {
            $existingId = DB::table('center_users')
                ->where(function ($q) use ($row) {
                    $q->where('email', $row->email)
                        ->orWhere('legacy_user_id', $row->id);
                })
                ->value('id');

            if ($existingId) {
                $legacyToCenterUser[$row->id] = $existingId;

                continue;
            }

            $id = DB::table('center_users')->insertGetId([
                'center_id' => $row->center_id,
                'name' => $row->name,
                'email' => $row->email,
                'phone' => $row->phone,
                'password' => $row->password,
                'status' => $row->status ?? 'active',
                'theme' => $row->theme ?? 'light',
                'avatar' => $row->avatar,
                'last_login_at' => $row->last_login_at,
                'legacy_user_id' => $row->id,
                'remember_token' => $row->remember_token,
                'created_at' => $row->created_at ?? $now,
                'updated_at' => $row->updated_at ?? $now,
            ]);

            $legacyToCenterUser[$row->id] = $id;
        }

        $centers = DB::table('centers')
            ->whereNotNull('manager_id')
            ->whereNull('manager_center_user_id')
            ->get(['id', 'manager_id']);

        foreach ($centers as $center) {
            $centerUserId = $legacyToCenterUser[$center->manager_id]
                ?? DB::table('center_users')->where('legacy_user_id', $center->manager_id)->value('id');

            if ($centerUserId) {
                DB::table('centers')
                    ->where('id', $center->id)
                    ->update(['manager_center_user_id' => $centerUserId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('centers')) {
            DB::table('centers')->update(['manager_center_user_id' => null]);
        }

        if (Schema::hasTable('center_users')) {
            DB::table('center_users')->whereNotNull('legacy_user_id')->delete();
        }

        if (Schema::hasTable('company_users')) {
            DB::table('company_users')->whereNotNull('legacy_user_id')->delete();
        }

        if (Schema::hasTable('admin_users')) {
            DB::table('admin_users')->whereNotNull('legacy_user_id')->delete();
        }
    }
};

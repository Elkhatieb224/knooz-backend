<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Correct defaults: contracted partners see net revenue; owned centers hide it by default.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('centers')->where('type', 'contracted')->update(['show_revenue' => true]);
        DB::table('centers')->where('type', 'owned')->update(['show_revenue' => false]);
    }

    public function down(): void
    {
        DB::table('centers')->where('type', 'owned')->update(['show_revenue' => true]);
        DB::table('centers')->where('type', 'contracted')->update(['show_revenue' => false]);
    }
};

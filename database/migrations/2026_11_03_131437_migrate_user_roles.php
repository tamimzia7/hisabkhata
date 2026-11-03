<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')->where('role', 'developer')->update(['role' => 'super-admin']);
        DB::table('users')->where('role', 'sub-admin')->update(['role' => 'admin']);
        DB::table('users')->where('role', 'customer-care')->update(['role' => 'developer']);

        DB::table('users')
            ->whereNull('role')
            ->where('mode', 'admin')
            ->update(['role' => 'admin']);

        DB::table('users')->whereNull('role')->where('mode', 'student')->update(['role' => 'student']);
        DB::table('users')->whereNull('role')->where('mode', 'personal')->update(['role' => 'family']);
        DB::table('users')->whereNull('role')->where('mode', 'business')->update(['role' => 'shop']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('role', 'developer')->update(['role' => 'customer-care']);
        DB::table('users')->where('role', 'admin')->update(['role' => 'sub-admin']);
        DB::table('users')->where('role', 'super-admin')->update(['role' => 'developer']);
        DB::table('users')->where('role', 'customer-care')->update(['role' => null]);

        DB::table('users')
            ->whereIn('role', ['student', 'family', 'shop'])
            ->update(['role' => null]);
    }
};

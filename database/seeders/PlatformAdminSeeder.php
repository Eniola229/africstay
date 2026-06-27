<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PlatformAdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('platform_admins')->truncate();

        DB::table('platform_admins')->insert([
            [
                'id' => Str::uuid(),
                'name' => 'Super Admin',
                'email' => 'superadmin@africstayhms.com',
                'password' => Hash::make('password123'),
                'role' => 'super_admin',
                'is_active' => true,
                'last_login_at' => null,
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
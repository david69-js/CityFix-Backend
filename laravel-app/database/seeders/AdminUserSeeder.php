<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure Admin role exists first
        $adminRole = Role::firstOrCreate(
            ['id' => 2], 
            ['name' => 'Admin', 'description' => 'System Administrator']
        );

        User::firstOrCreate(
            ['email' => 'admin@cityfix.com'],
            [
                'first_name' => 'System',
                'last_name' => 'Admin',
                'password' => Hash::make('admin123'), // Default password
                'phone' => '1234567890',
                'role_id' => $adminRole->id,
            ]
        );
    }
}

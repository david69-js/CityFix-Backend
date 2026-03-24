<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'name' => 'User', 'description' => 'Standard user'],
            ['id' => 2, 'name' => 'Admin', 'description' => 'System Administrator'],
            ['id' => 3, 'name' => 'Worker', 'description' => 'City Worker'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['id' => $role['id']], $role);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => 'view_users', 'description' => 'Can view normal users'],
            ['name' => 'edit_users', 'description' => 'Can edit users'],
            ['name' => 'delete_users', 'description' => 'Can delete users'],
            ['name' => 'view_issues', 'description' => 'Can view reported issues'],
            ['name' => 'edit_issues', 'description' => 'Can update existing issues'],
            ['name' => 'resolve_issues', 'description' => 'Can mark issues as resolved'],
            ['name' => 'manage_roles', 'description' => 'Can manage user roles'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name']], $permission);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\InvitationCode;
use Illuminate\Database\Seeder;

class InvitationCodeSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'Admin')->first();
        $workerRole = Role::where('name', 'Worker')->first();

        if ($adminRole) {
            InvitationCode::firstOrCreate(
                ['code' => 'ADMIN2026'],
                [
                    'role_id' => $adminRole->id,
                    'is_active' => true,
                    'max_uses' => 10,
                ]
            );
        }

        if ($workerRole) {
            InvitationCode::firstOrCreate(
                ['code' => 'WORKER2026'],
                [
                    'role_id' => $workerRole->id,
                    'is_active' => true,
                    'max_uses' => 100,
                ]
            );
        }
    }
}
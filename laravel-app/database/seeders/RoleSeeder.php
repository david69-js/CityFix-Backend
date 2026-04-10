<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(
            ['name' => 'Admin'],
            ['description' => 'Administrador del sistema']
        );

        Role::firstOrCreate(
            ['name' => 'Worker'],
            ['description' => 'Trabajador del sistema']
        );

        Role::firstOrCreate(
            ['name' => 'Citizen'],
            ['description' => 'Ciudadano del sistema']
        );
    }
}
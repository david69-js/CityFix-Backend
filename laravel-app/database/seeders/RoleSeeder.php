<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(
            ['name' => 'Citizen'],
            ['description' => 'Ciudadano que reporta incidencias']
        );

        Role::firstOrCreate(
            ['name' => 'Admin'],
            ['description' => 'Administrador del sistema']
        );

        Role::firstOrCreate(
            ['name' => 'Worker'],
            ['description' => 'Trabajador del sistema']
        );
    }
}
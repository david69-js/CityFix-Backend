<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $citizenRole = Role::firstOrCreate(
            ['name' => 'Citizen'],
            ['description' => 'Ciudadano que reporta incidencias']
        );

        $workerRole = Role::firstOrCreate(
            ['name' => 'Worker'],
            ['description' => 'Trabajador que atiende los reportes']
        );

        $citizens = [
            ['first_name' => 'Carlos', 'last_name' => 'García', 'email' => 'carlos.garcia@example.com', 'phone' => '612345789'],
            ['first_name' => 'María', 'last_name' => 'López', 'email' => 'maria.lopez@example.com', 'phone' => '698765432'],
            ['first_name' => 'José', 'last_name' => 'Martínez', 'email' => 'jose.martinez@example.com', 'phone' => '655123987'],
            ['first_name' => 'Ana', 'last_name' => 'Rodríguez', 'email' => 'ana.rodriguez@example.com', 'phone' => '677888999'],
            ['first_name' => 'David', 'last_name' => 'Fernández', 'email' => 'david.fernandez@example.com', 'phone' => '634567890'],
            ['first_name' => 'Laura', 'last_name' => 'Sánchez', 'email' => 'laura.sanchez@example.com', 'phone' => '622334455'],
            ['first_name' => 'Manuel', 'last_name' => 'Ramírez', 'email' => 'manuel.ramirez@example.com', 'phone' => '666778899'],
            ['first_name' => 'Sofía', 'last_name' => 'Torres', 'email' => 'sofia.torres@example.com', 'phone' => '611223344'],
            ['first_name' => 'Alejandro', 'last_name' => 'Díaz', 'email' => 'alejandro.diaz@example.com', 'phone' => '644556677'],
            ['first_name' => 'Elena', 'last_name' => 'Moreno', 'email' => 'elena.moreno@example.com', 'phone' => '688990011'],
        ];

        foreach ($citizens as $citizen) {
            User::firstOrCreate(
                ['email' => $citizen['email']],
                [
                    'first_name' => $citizen['first_name'],
                    'last_name' => $citizen['last_name'],
                    'password' => Hash::make('password'),
                    'phone' => $citizen['phone'],
                    'role_id' => $citizenRole->id,
                ]
            );
        }

        $workers = [
            ['first_name' => 'Pedro', 'last_name' => 'Gómez'],
            ['first_name' => 'Lucía', 'last_name' => 'Herrera'],
            ['first_name' => 'Javier', 'last_name' => 'Ruiz'],
            ['first_name' => 'Carmen', 'last_name' => 'Vargas'],
            ['first_name' => 'Raúl', 'last_name' => 'Castillo'],
        ];

        foreach ($workers as $i => $worker) {
            User::firstOrCreate(
                ['email' => 'worker' . ($i + 1) . '@cityfix.com'],
                [
                    'first_name' => $worker['first_name'],
                    'last_name' => $worker['last_name'],
                    'password' => Hash::make('password'),
                    'phone' => '6000000' . ($i + 1),
                    'role_id' => $workerRole->id,
                ]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_ES');

        $citizenRole = Role::firstOrCreate(
            ['name' => 'Citizen'],
            ['description' => 'Ciudadano que reporta incidencias']
        );

        $workerRole = Role::firstOrCreate(
            ['name' => 'Worker'],
            ['description' => 'Trabajador que atiende los reportes']
        );

        // Crear 10 ciudadanos
        for ($i = 0; $i < 10; $i++) {
            User::firstOrCreate(
                ['email' => $faker->unique()->safeEmail],
                [
                    'first_name' => $faker->firstName,
                    'last_name' => $faker->lastName,
                    'password' => Hash::make('password'),
                    'phone' => $faker->phoneNumber,
                    'role_id' => $citizenRole->id,
                ]
            );
        }

        // Crear 5 trabajadores
        for ($i = 0; $i < 5; $i++) {
            User::firstOrCreate(
                ['email' => 'worker' . ($i + 1) . '@cityfix.com'],
                [
                    'first_name' => 'Trabajador',
                    'last_name' => $faker->lastName,
                    'password' => Hash::make('password'),
                    'phone' => $faker->phoneNumber,
                    'role_id' => $workerRole->id,
                ]
            );
        }
    }
}

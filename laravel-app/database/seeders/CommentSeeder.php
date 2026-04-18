<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_ES');
        $users = User::all();
        $issues = Issue::all();

        if ($users->isEmpty() || $issues->isEmpty()) {
            $this->command->warn('Asegúrate de tener Usuarios y Reportes antes de correr este seeder.');
            return;
        }

        foreach ($issues as $issue) {
            // Entre 2 y 8 comentarios por reporte
            $numComments = rand(2, 8);
            
            for ($i = 0; $i < $numComments; $i++) {
                Comment::create([
                    'issue_id' => $issue->id,
                    'user_id' => $users->random()->id,
                    'comment' => $faker->sentence(rand(6, 15)),
                    'created_at' => $faker->dateTimeBetween($issue->created_at, 'now'),
                ]);
            }
        }
    }
}

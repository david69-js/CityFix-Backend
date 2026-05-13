<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    private array $comments = [
        'Ya reporté esto hace semanas y no hay respuesta.',
        'Apoyo la solicitud, es urgente que lo atiendan.',
        'Este problema afecta a toda la colonia.',
        'Gracias por levantar el reporte, vecino.',
        'Ojalá las autoridades hagan algo pronto.',
        '¿Alguien sabe a qué dependencia le toca resolver esto?',
        'Comparto el reporte, es exactamente lo que está pasando.',
        'Esperemos que con la presión ciudadana se resuelva.',
        'Ya van varias veces que pasa esto, es indignante.',
        'Hay que organizarnos para exigir una solución.',
        'Si todos reportamos, tal vez nos hagan caso.',
        'La situación es insostenible, necesitamos apoyo.',
    ];

    public function run(): void
    {
        $users = User::all();
        $issues = Issue::all();

        if ($users->isEmpty() || $issues->isEmpty()) {
            $this->command->warn('Asegúrate de tener Usuarios y Reportes antes de correr este seeder.');
            return;
        }

        foreach ($issues as $issue) {
            $numComments = rand(2, 8);

            for ($i = 0; $i < $numComments; $i++) {
                $createdAt = $issue->created_at->copy()->addDays(rand(0, now()->diffInDays($issue->created_at)))->addHours(rand(0, 23));

                Comment::create([
                    'issue_id' => $issue->id,
                    'user_id' => $users->random()->id,
                    'comment' => $this->comments[array_rand($this->comments)],
                    'created_at' => $createdAt,
                ]);
            }
        }
    }
}

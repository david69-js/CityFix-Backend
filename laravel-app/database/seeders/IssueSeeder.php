<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Issue;
use App\Models\IssueStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class IssueSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_ES');
        $users = User::all();
        $categories = Category::all();
        $statuses = IssueStatus::all();

        if ($users->isEmpty() || $categories->isEmpty() || $statuses->isEmpty()) {
            $this->command->warn('Asegúrate de tener Usuarios, Categorías y Estados antes de correr este seeder.');
            return;
        }

        // Centro hipotético (Ciudad de México como ejemplo)
        $latMin = 19.38;
        $latMax = 19.45;
        $lonMin = -99.20;
        $lonMax = -99.10;

        $imageThemes = ['street', 'pothole', 'garbage', 'lights', 'water', 'city'];

        for ($i = 0; $i < 25; $i++) {
            $issue = Issue::create([
                'user_id' => $users->random()->id,
                'category_id' => $categories->random()->id,
                'title' => $faker->sentence(4),
                'description' => $faker->paragraph(2),
                'location' => $faker->streetAddress . ', ' . $faker->city,
                'latitude' => $faker->latitude($latMin, $latMax),
                'longitude' => $faker->longitude($lonMin, $lonMax),
                'status_id' => $statuses->random()->id,
                'created_at' => $faker->dateTimeBetween('-1 month', 'now'),
            ]);

            // Añadir una imagen falsa
            $theme = $imageThemes[array_rand($imageThemes)];
            $issue->images()->create([
                'image_url' => "https://source.unsplash.com/featured/800x600?{$theme}&sig=" . $faker->randomNumber(5),
            ]);

            // Añadir algunos votos aleatorios
            $upvoteCount = rand(0, min(5, $users->count()));
            if ($upvoteCount > 0) {
                $voters = $users->random($upvoteCount);
                // Si solo hay uno, random() puede devolver el modelo directamente en algunas versiones,
                // nos aseguramos de que sea una colección para iterar.
                $votersCollection = ($voters instanceof \App\Models\User) ? collect([$voters]) : $voters;
                
                foreach ($votersCollection as $voter) {
                    try {
                        $issue->upvotes()->firstOrCreate(['user_id' => $voter->id]);
                    } catch (\Illuminate\Database\QueryException $e) {
                        // Ignorar
                    }
                }
            }

            // Si está resuelto o en proceso, crear historial
            if ($issue->status->id != 1) { // 1 es Pendiente
                $issue->history()->create([
                    'status_id' => $issue->status_id,
                    'changed_by' => $users->random()->id,
                    'changed_at' => $issue->created_at->addDays(rand(1, 5)),
                ]);
            }
        }
    }
}

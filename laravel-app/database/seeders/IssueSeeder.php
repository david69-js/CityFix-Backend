<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Issue;
use App\Models\IssueStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class IssueSeeder extends Seeder
{
    private array $titles = [
        'Bache peligroso en la esquina',
        'Alumbrado público fundido',
        'Basura acumulada en la calle',
        'Coladera tapada',
        'Banqueta rota',
        'Árbol caído sobre la vía',
        'Fuga de agua en tubería',
        'Señal de tránsito dañada',
        'Terreno baldío sin mantenimiento',
        'Semáforo descompuesto',
        'Grafiti en fachada histórica',
        'Poste de luz a punto de caer',
        'Cárcamo de drenaje obstruido',
        'Parque infantil en mal estado',
        'Calle sin pavimentar',
        'Contenedor de basura desbordado',
        'Luminaria pública intermitente',
        'Banqueta levantada por raíces',
        'Tapa de registro faltante',
        'Cableado eléctrico colgando',
    ];

    private array $descriptions = [
        'Los vecinos reportan que este problema lleva varias semanas sin solución y representa un riesgo para peatones y conductores.',
        'Es necesario que las autoridades correspondientes tomen cartas en el asunto antes de que ocurra un accidente grave.',
        'Se ha solicitado apoyo en múltiples ocasiones pero no se recibe respuesta por parte del ayuntamiento.',
        'La situación empeora con las lluvias, causando inundaciones y malos olores en toda la colonia.',
        'Los residentes de la zona han organizado recolectas de firmas para exigir una pronta solución.',
    ];

    private array $streets = [
        'Av. Reforma', 'Calle Madero', 'Av. Insurgentes', 'Calle Juárez', 'Blvd. Independencia',
        'Av. Universidad', 'Calle Hidalgo', 'Paseo de la Reforma', 'Calle Morelos', 'Av. Chapultepec',
    ];

    private array $cities = [
        'Ciudad de México', 'Monterrey', 'Guadalajara', 'Puebla', 'Querétaro',
    ];

    private array $imageThemes = ['street', 'pothole', 'garbage', 'lights', 'water', 'city'];

    public function run(): void
    {
        $users = User::all();
        $categories = Category::all();
        $statuses = IssueStatus::all();

        if ($users->isEmpty() || $categories->isEmpty() || $statuses->isEmpty()) {
            $this->command->warn('Asegúrate de tener Usuarios, Categorías y Estados antes de correr este seeder.');
            return;
        }

        $latMin = 19.38;
        $latMax = 19.45;
        $lonMin = -99.20;
        $lonMax = -99.10;

        for ($i = 0; $i < 25; $i++) {
            $createdAt = now()->subDays(rand(1, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59));

            $issue = Issue::create([
                'user_id' => $users->random()->id,
                'category_id' => $categories->random()->id,
                'title' => $this->titles[array_rand($this->titles)],
                'description' => $this->descriptions[array_rand($this->descriptions)],
                'location' => $this->streets[array_rand($this->streets)] . ', ' . $this->cities[array_rand($this->cities)],
                'latitude' => round(($latMin + mt_rand() / mt_getrandmax() * ($latMax - $latMin)), 6),
                'longitude' => round(($lonMin + mt_rand() / mt_getrandmax() * ($lonMax - $lonMin)), 6),
                'status_id' => $statuses->random()->id,
                'created_at' => $createdAt,
            ]);

            $theme = $this->imageThemes[array_rand($this->imageThemes)];
            $issue->images()->create([
                'image_url' => "https://source.unsplash.com/featured/800x600?{$theme}&sig=" . rand(10000, 99999),
            ]);

            $upvoteCount = rand(0, min(5, $users->count()));
            if ($upvoteCount > 0) {
                $voters = $users->random($upvoteCount);
                $votersCollection = ($voters instanceof \App\Models\User) ? collect([$voters]) : $voters;

                foreach ($votersCollection as $voter) {
                    try {
                        $issue->upvotes()->firstOrCreate(['user_id' => $voter->id]);
                    } catch (\Illuminate\Database\QueryException $e) {
                        // Ignorar
                    }
                }
            }

            if ($issue->status->id != 1) {
                $issue->history()->create([
                    'status_id' => $issue->status_id,
                    'changed_by' => $users->random()->id,
                    'changed_at' => $createdAt->addDays(rand(1, 5)),
                ]);
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Baches', 'icon' => 'road'],
            ['name' => 'Fuga de Agua', 'icon' => 'tint'],
            ['name' => 'Iluminación', 'icon' => 'lightbulb'],
            ['name' => 'Basura', 'icon' => 'trash'],
            ['name' => 'Vandalismo', 'icon' => 'hammer'],
            ['name' => 'Semáforos', 'icon' => 'traffic-light'],
            ['name' => 'Árboles Caídos', 'icon' => 'tree'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['name' => $cat['name']],
                ['icon' => $cat['icon']]
            );
        }
    }
}

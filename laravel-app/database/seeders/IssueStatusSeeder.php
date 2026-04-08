<?php

namespace Database\Seeders;

use App\Models\IssueStatus;
use Illuminate\Database\Seeder;

class IssueStatusSeeder extends Seeder
{
    public function run(): void
    {
        IssueStatus::firstOrCreate(
            ['name' => 'Pendiente'],
            ['color' => '#f59e0b', 'sort_order' => 1]
        );

        IssueStatus::firstOrCreate(
            ['name' => 'En proceso'],
            ['color' => '#3b82f6', 'sort_order' => 2]
        );

        IssueStatus::firstOrCreate(
            ['name' => 'Resuelto'],
            ['color' => '#10b981', 'sort_order' => 3]
        );
    }
}
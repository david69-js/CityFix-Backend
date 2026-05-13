<?php

namespace Database\Seeders;

use App\Models\AssignmentStatus;
use Illuminate\Database\Seeder;

class AssignmentStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            'Pending',
            'In Progress',
            'Completed',
            'On Hold',
            'Cancelled',
        ];

        foreach ($statuses as $status) {
            AssignmentStatus::firstOrCreate(['name' => $status]);
        }
    }
}

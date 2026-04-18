<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            AdminUserSeeder::class,
            UserSeeder::class,
            NotificationSeeder::class,
            IssueStatusSeeder::class,
            InvitationCodeSeeder::class,
            CategorySeeder::class,
            IssueSeeder::class,
            CommentSeeder::class,


        ]);
    }
}

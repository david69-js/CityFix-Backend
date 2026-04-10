<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\User;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find the admin user to attach a sample notification
        $admin = User::where('email', 'admin@cityfix.com')->first();
        
        if ($admin) {
            Notification::firstOrCreate(
                ['title' => 'Welcome to CityFix', 'user_id' => $admin->id],
                [
                    'type' => 'system_alert',
                    'message' => 'Your admin account has been configured successfully. Check the dashboard to review pending issues.',
                    'is_read' => false,
                ]
            );
        }
    }
}

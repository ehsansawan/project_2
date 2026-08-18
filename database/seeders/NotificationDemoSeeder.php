<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('notifications')->truncate();

        $clients = User::role('client')->orderBy('id')->take(10)->get();

        $templates = [
            ['title' => 'Verification Approved', 'body' => 'Your account has been verified successfully.', 'data' => ['type' => 'verification', 'status' => 'approved']],
            ['title' => 'Certificate Approved', 'body' => 'Your certificate has been approved.', 'data' => ['type' => 'certificate', 'status' => 'approved']],
            ['title' => 'Certificate Rejected', 'body' => 'Your certificate was rejected. Reason: blurry image.', 'data' => ['type' => 'certificate', 'status' => 'rejected']],
            ['title' => 'Volunteer Application Approved', 'body' => 'Your application to volunteer has been approved.', 'data' => ['type' => 'volunteer_application', 'status' => 'approved']],
            ['title' => 'Volunteer Application Rejected', 'body' => 'Your application to volunteer was not accepted.', 'data' => ['type' => 'volunteer_application', 'status' => 'rejected']],
            ['title' => 'Donation Recorded', 'body' => 'Thank you! Your donation has been recorded.', 'data' => ['type' => 'donation']],
        ];

        foreach ($clients as $index => $client) {
            $notificationCount = 1 + ($index % 3);

            for ($i = 0; $i < $notificationCount; $i++) {
                $template = $templates[($index + $i) % count($templates)];

                Notification::create([
                    'user_id' => $client->id,
                    'title' => $template['title'],
                    'body' => $template['body'],
                    'data' => $template['data'],
                    'is_read' => $i > 0, // most recent one stays unread, older ones read
                ]);
            }
        }
    }
}

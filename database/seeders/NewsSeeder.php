<?php

namespace Database\Seeders;

use App\Models\MapPin;
use App\Models\News;
use App\Models\NewsMedia;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('news_media')->truncate();
        DB::table('news')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $superAdmin = User::role('super-admin')->first();
        $admins = User::role('admin')->get();

        $baseLat = 33.5138;
        $baseLng = 36.2765;

        $items = [
            [
                'title' => 'Water Supply Maintenance This Weekend',
                'description' => 'Water supply will be interrupted in the eastern district on Saturday from 8am to 2pm for scheduled pipe maintenance.',
                'type' => 'announcement',
                'target_audience' => 'specific_area',
                'status' => 'approved',
                'pinned' => true,
            ],
            [
                'title' => 'Flash Flood Warning',
                'description' => 'Heavy rain expected tonight. Residents near the river are advised to avoid low-lying areas.',
                'type' => 'emergency_alert',
                'target_audience' => 'specific_area',
                'status' => 'approved',
                'pinned' => true,
            ],
            [
                'title' => 'New Municipal Building Permits Portal Launched',
                'description' => 'Citizens can now submit building permit applications entirely online through the new portal.',
                'type' => 'news',
                'target_audience' => 'all',
                'status' => 'approved',
                'pinned' => false,
            ],
            [
                'title' => 'Call for Volunteers with Medical Training',
                'description' => 'The municipality is looking for citizens with medical or first-aid skills to join upcoming community health initiatives.',
                'type' => 'news',
                'target_audience' => 'specific_skills',
                'status' => 'approved',
                'pinned' => false,
            ],
            [
                'title' => 'Draft: Road Closure Notice for Downtown Festival',
                'description' => 'Main Street will be closed to traffic during the upcoming downtown festival.',
                'type' => 'announcement',
                'target_audience' => 'all',
                'status' => 'pending_review',
                'pinned' => false,
            ],
            [
                'title' => 'Rejected: Unverified Rumor About Service Fees',
                'description' => 'Claims about new service fees could not be verified against official municipal decisions.',
                'type' => 'news',
                'target_audience' => 'all',
                'status' => 'rejected',
                'pinned' => false,
            ],
        ];

        foreach ($items as $index => $item) {
            $pin = null;
            if ($item['pinned']) {
                $pin = MapPin::create([
                    'latitude' => $baseLat + (mt_rand(-200, 200) / 10000),
                    'longitude' => $baseLng + (mt_rand(-200, 200) / 10000),
                    'type' => 'other',
                    'description' => $item['title'],
                ]);
            }

            $isApproved = $item['status'] === 'approved';
            $isRejected = $item['status'] === 'rejected';
            $reviewer = $admins->count() > 0 ? $admins[$index % $admins->count()] : $superAdmin;

            $news = News::create([
                'title' => $item['title'],
                'description' => $item['description'],
                'type' => $item['type'],
                'target_audience' => $item['target_audience'],
                'created_by' => $superAdmin->id,
                'status' => $item['status'],
                'rejection_reason' => $isRejected ? 'Content could not be verified against an official source.' : null,
                'reviewed_by' => ($isApproved || $isRejected) ? $reviewer->id : null,
                'reviewed_at' => ($isApproved || $isRejected) ? now()->subDays(1) : null,
                'published_at' => $isApproved ? now()->subHours(6) : null,
                'pin_id' => $pin?->id,
            ]);

            NewsMedia::create([
                'news_id' => $news->id,
                'file_path' => 'news/demo/' . $news->id . '-cover.jpg',
                'media_type' => 'image',
            ]);
        }
    }
}

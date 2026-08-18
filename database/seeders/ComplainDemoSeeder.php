<?php

namespace Database\Seeders;

use App\Models\Complain;
use App\Models\ComplainCategory;
use App\Models\ComplainEndorsement;
use App\Models\ComplainMedia;
use App\Models\MapPin;
use App\Models\Report;
use App\Models\ReportType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComplainDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('reports')->truncate();
        DB::table('complain_endorsements')->truncate();
        DB::table('complain_media')->truncate();
        DB::table('complains')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $clients = User::role('client')->orderBy('id')->get();
        $admins = User::role('admin')->get();
        $categories = ComplainCategory::all();
        $reportTypes = ReportType::all();

        $baseLat = 33.5138;
        $baseLng = 36.2765;

        // MapPin.type is a restricted enum (hospital/streetlight/parking/pothole/garbage_bin/other) -
        // map each complaint category to the closest valid pin type.
        $pinTypeByCategory = [
            'Roads & Potholes' => 'pothole',
            'Waste & Cleanliness' => 'garbage_bin',
            'Streetlights & Electricity' => 'streetlight',
            'Water & Sewage' => 'other',
            'Public Services' => 'other',
            'Other' => 'other',
        ];

        $complaintsData = [
            ['title' => 'Large pothole on Al-Thawra Street', 'type' => 'individual', 'status' => 'published', 'category' => 'Roads & Potholes'],
            ['title' => 'Overflowing garbage bins near the market', 'type' => 'collective', 'status' => 'in_progress', 'category' => 'Waste & Cleanliness'],
            ['title' => 'Broken streetlight, dark alley at night', 'type' => 'individual', 'status' => 'under_review', 'category' => 'Streetlights & Electricity'],
            ['title' => 'Sewage leak flooding the sidewalk', 'type' => 'emergency', 'status' => 'under_review', 'category' => 'Water & Sewage'],
            ['title' => 'No water supply for three days', 'type' => 'collective', 'status' => 'published', 'category' => 'Water & Sewage'],
            ['title' => 'Illegal dumping behind the school', 'type' => 'collective', 'status' => 'closed', 'category' => 'Waste & Cleanliness'],
            ['title' => 'Damaged public bench in the park', 'type' => 'individual', 'status' => 'rejected', 'category' => 'Public Services'],
            ['title' => 'Traffic light malfunctioning at the intersection', 'type' => 'emergency', 'status' => 'published', 'category' => 'Streetlights & Electricity'],
            ['title' => 'Cracked sidewalk causing trip hazard', 'type' => 'individual', 'status' => 'under_review', 'category' => 'Roads & Potholes'],
            ['title' => 'Noise complaint about construction hours', 'type' => 'individual', 'status' => 'closed', 'category' => 'Other'],
        ];

        foreach ($complaintsData as $index => $data) {
            $author = $clients[$index % $clients->count()];
            $category = $categories->firstWhere('name', $data['category']) ?? $categories->first();

            $pin = MapPin::create([
                'latitude' => $baseLat + (mt_rand(-300, 300) / 10000),
                'longitude' => $baseLng + (mt_rand(-300, 300) / 10000),
                'type' => $pinTypeByCategory[$data['category']] ?? 'other',
                'description' => $data['title'],
            ]);

            $complain = Complain::create([
                'user_id' => $author->id,
                'title' => $data['title'],
                'description' => $data['title'] . '. This has been an ongoing issue affecting residents in the area and needs municipal attention.',
                'type' => $data['type'],
                'category_id' => $category->id,
                'status' => $data['status'],
                'priority_score' => (int) $category->weight,
                'pin_id' => $pin->id,
                'decision_reason' => $data['status'] === 'rejected' ? 'Unable to verify the reported issue at the given location.' : null,
            ]);

            ComplainMedia::create([
                'complain_id' => $complain->id,
                'media_type' => 'image',
                'file_path' => 'complains/demo/' . $complain->id . '-1.jpg',
            ]);

            // A few endorsements from other clients.
            $endorserCount = 1 + ($index % 4);
            for ($e = 0; $e < $endorserCount; $e++) {
                $endorser = $clients[($index + $e + 1) % $clients->count()];
                if ($endorser->id === $author->id) {
                    continue;
                }
                ComplainEndorsement::firstOrCreate([
                    'complain_id' => $complain->id,
                    'user_id' => $endorser->id,
                ], [
                    'action' => 'support',
                ]);
            }

            // Every third complaint has a content report against it.
            if ($index % 3 === 0) {
                Report::create([
                    'user_id' => $clients[($index + 2) % $clients->count()]->id,
                    'complain_id' => $complain->id,
                    'type_id' => $reportTypes->random()->id,
                    'description' => 'This complaint looks like a duplicate of one already reported.',
                    'status' => $index % 2 === 0 ? 'pending' : 'approved',
                    'reported_at' => now()->subDays(2),
                    'reviewed_at' => $index % 2 === 0 ? null : now()->subDay(),
                    'reviewed_by' => $index % 2 === 0 ? null : $admins->random()->id,
                    'decision_reason' => $index % 2 === 0 ? null : 'Confirmed duplicate, original complaint kept.',
                ]);
            }
        }
    }
}

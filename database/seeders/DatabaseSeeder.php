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
            ComplainCategorySeeder::class,
            UserSeeder::class,
            ReportTypeSeeder::class,
            ServiceSeeder::class,
        ]);

        // Demo data for the interview build - new seeders only, appended
        // after the existing ones above (which are left untouched).
        $this->call([
            ScoreRuleSeeder::class,
            SkillCertificateSeeder::class,
            VerificationDemoSeeder::class,
            PropertyLicenseSeeder::class,
            ProjectDemoSeeder::class,
            ComplainDemoSeeder::class,
            QueueDemoSeeder::class,
            NewsSeeder::class,
            NotificationDemoSeeder::class,
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\ScoreRule;
use Illuminate\Database\Seeder;

class ScoreRuleSeeder extends Seeder
{
    public function run(): void
    {
        ScoreRule::query()->delete();

        $rules = [
            ['name' => 'Complaint Approved', 'type' => 'citizenship', 'points' => 5],
            ['name' => 'Complaint Rejected', 'type' => 'citizenship', 'points' => -5],
            ['name' => 'Volunteering Completed', 'type' => 'citizenship', 'points' => 10],
            ['name' => 'Donation Recorded', 'type' => 'citizenship', 'points' => 10],
            ['name' => 'Identity Verified', 'type' => 'credibility', 'points' => 20],
            ['name' => 'False Report Filed', 'type' => 'credibility', 'points' => -15],
            ['name' => 'Certificate Verified', 'type' => 'credibility', 'points' => 10],
        ];

        foreach ($rules as $rule) {
            ScoreRule::create($rule);
        }
    }
}

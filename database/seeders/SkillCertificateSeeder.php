<?php

namespace Database\Seeders;

use App\Enums\CertificateRejectionReason;
use App\Enums\CertificateStatus;
use App\Enums\SkillType;
use App\Models\User;
use App\Models\UserCertificate;
use App\Models\UserSkill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SkillCertificateSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('user_certificates')->truncate();
        DB::table('user_skills')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $skillsByType = [
            SkillType::Technical->value => ['Electrical Wiring', 'Plumbing Repair', 'Solar Panel Installation'],
            SkillType::Craftsmanship->value => ['Carpentry', 'Tailoring', 'Blacksmithing'],
            SkillType::Construction->value => ['Masonry', 'Concrete Work', 'Roofing'],
            SkillType::Social->value => ['Community Outreach', 'Arabic-English Translation', 'Event Organizing'],
            SkillType::Educational->value => ['Math Tutoring', 'Literacy Training', 'Computer Skills Training'],
            SkillType::Medical->value => ['First Aid', 'Nursing Assistance', 'CPR Certified'],
            SkillType::Driving->value => ['Heavy Vehicle Driving', 'Ambulance Driving'],
            SkillType::Logistics->value => ['Warehouse Management', 'Supply Coordination'],
            SkillType::Environmental->value => ['Tree Planting', 'Recycling Coordination', 'Waste Sorting'],
            SkillType::Other->value => ['General Labor'],
        ];

        $clients = User::role('client')->orderBy('id')->get();
        $types = array_keys($skillsByType);

        foreach ($clients as $index => $client) {
            $skillCount = 1 + ($index % 2); // 1 or 2 skills per client
            $chosenTypes = collect($types)->shuffle()->take($skillCount);

            foreach ($chosenTypes as $type) {
                $name = collect($skillsByType[$type])->random();

                $skill = UserSkill::create([
                    'user_id' => $client->id,
                    'name' => $name,
                    'type' => $type,
                ]);

                // Roughly two thirds of skills have a certificate attached.
                if ($index % 3 !== 0) {
                    $statusRoll = $index % 4;
                    $status = match (true) {
                        $statusRoll === 0 => CertificateStatus::Rejected->value,
                        $statusRoll === 1 => CertificateStatus::Pending->value,
                        default => CertificateStatus::Approved->value,
                    };

                    UserCertificate::create([
                        'user_skill_id' => $skill->id,
                        'file_path' => "https://picsum.photos/seed/cert-{$skill->id}/700/900",
                        'status' => $status,
                        'rejection_reason' => $status === CertificateStatus::Rejected->value
                            ? CertificateRejectionReason::MissingInformation->value
                            : null,
                    ]);
                }
            }
        }
    }
}

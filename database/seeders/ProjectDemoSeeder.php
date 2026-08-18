<?php

namespace Database\Seeders;

use App\Enums\AuditAction;
use App\Enums\ProjectStatus;
use App\Enums\SkillType;
use App\Models\AuditLog;
use App\Models\Donation;
use App\Models\Profile;
use App\Models\Project;
use App\Models\ProjectParticipant;
use App\Models\ProjectRequirement;
use App\Models\ProjectVote;
use App\Models\User;
use App\Models\UserSkill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectDemoSeeder extends Seeder
{
    /**
     * Same formula as ProjectVoteService::calculateWeight() - kept in sync
     * manually since seeders intentionally bypass the service layer.
     */
    private function voteWeight(int $citizenshipScore): float
    {
        return 1 + sqrt(max($citizenshipScore, 0));
    }

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('donations')->truncate();
        DB::table('project_votes')->truncate();
        DB::table('project_participants')->truncate();
        DB::table('project_requirements')->truncate();
        DB::table('project_media')->truncate();
        DB::table('projects')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $clients = User::role('client')->orderBy('id')->get();
        $admins = User::role('admin')->get();
        $superAdmin = User::role('super-admin')->first();

        // Give a few clients a spread of citizenship scores so vote weights
        // are actually interesting in the demo, instead of everyone tied at 50.
        $scoreOverrides = [0 => 90, 1 => 15, 2 => 65, 3 => 30, 4 => 100];
        foreach ($scoreOverrides as $index => $score) {
            if (isset($clients[$index])) {
                Profile::where('user_id', $clients[$index]->id)->update(['citizenship_score' => $score]);
            }
        }
        $clients = $clients->fresh();

        $owner = fn (int $i) => $clients[$i % $clients->count()]->id;

        // 1) Votable, submitted, actively collecting votes.
        $park = Project::create([
            'user_id' => $owner(0),
            'name' => 'Renovate Al-Midan Public Park',
            'description' => 'Restore benches, walking paths, and lighting in the neighborhood park.',
            'type' => 'municipal',
            'budget' => 45000,
            'is_votable' => true,
            'is_voluntary' => false,
            'is_donation' => false,
            'status' => ProjectStatus::Submitted->value,
        ]);
        $this->castVotes($park, [
            [1, true], [3, true], [5, true], [7, false], [9, true],
        ], $clients);

        // 2) Votable, submitted, high approval.
        $library = Project::create([
            'user_id' => $owner(1),
            'name' => 'Build New Library Wing',
            'description' => 'Add a children\'s reading wing and study rooms to the central library.',
            'type' => 'municipal',
            'budget' => 120000,
            'is_votable' => true,
            'is_voluntary' => false,
            'is_donation' => false,
            'status' => ProjectStatus::Submitted->value,
        ]);
        $this->castVotes($library, [
            [0, true], [2, true], [4, true], [6, true], [8, true], [10, false],
        ], $clients);

        // 3) Non-votable, submitted -> sits in the super-admin's non-votable
        // review queue (pendingApproval()).
        Project::create([
            'user_id' => $owner(2),
            'name' => 'Repave Souq Al-Hamidiyah Street',
            'description' => 'Full repaving and drainage fix for the market street.',
            'type' => 'municipal',
            'budget' => 80000,
            'is_votable' => false,
            'is_voluntary' => false,
            'is_donation' => false,
            'status' => ProjectStatus::Submitted->value,
        ]);

        // 4) Votable, already approved -> voting has "concluded" (a good demo
        // that citizen/admin voting-stats endpoints still show final results
        // after a project leaves the submitted stage).
        $solar = Project::create([
            'user_id' => $owner(3),
            'name' => 'Install Solar Streetlights',
            'description' => 'Replace old streetlights with solar-powered LED fixtures on Main Avenue.',
            'type' => 'municipal',
            'budget' => 60000,
            'is_votable' => true,
            'is_voluntary' => false,
            'is_donation' => false,
            'status' => ProjectStatus::Submitted->value,
        ]);
        $this->castVotes($solar, [
            [0, true], [1, true], [2, true], [3, false], [4, true],
        ], $clients);
        $solar->update(['status' => ProjectStatus::Approved->value]);

        // 5) Voluntary project with skill-specific + general requirements and
        // a mix of pending/approved/rejected volunteer applications.
        $clinic = Project::create([
            'user_id' => $owner(4),
            'name' => 'Community Health Clinic Expansion',
            'description' => 'Volunteers needed to help staff and set up the new clinic wing.',
            'type' => 'community',
            'is_votable' => false,
            'is_voluntary' => true,
            'is_donation' => false,
            'status' => ProjectStatus::Approved->value,
        ]);
        $medicalReq = ProjectRequirement::create([
            'project_id' => $clinic->id,
            'skill_name' => 'First Aid / Nursing',
            'skill_type' => SkillType::Medical->value,
            'required_count' => 3,
            'is_need_certificate' => true,
        ]);
        $socialReq = ProjectRequirement::create([
            'project_id' => $clinic->id,
            'skill_name' => 'Community Outreach',
            'skill_type' => SkillType::Social->value,
            'required_count' => 2,
            'is_need_certificate' => false,
        ]);
        $generalReq = ProjectRequirement::create([
            'project_id' => $clinic->id,
            'skill_name' => null,
            'skill_type' => null,
            'required_count' => 5,
            'is_need_certificate' => false,
        ]);
        $this->seedVolunteers($clinic, [$medicalReq, $socialReq, $generalReq], $clients, $admins);

        // 6) Both voluntary AND donatable at once.
        $riverside = Project::create([
            'user_id' => $owner(5),
            'name' => 'Riverside Cleanup Day',
            'description' => 'A one-day cleanup of the riverside walking trail, funded partly by donations.',
            'type' => 'community',
            'budget' => 15000,
            'is_votable' => false,
            'is_voluntary' => true,
            'is_donation' => true,
            'status' => ProjectStatus::Approved->value,
        ]);
        $riversideGeneral = ProjectRequirement::create([
            'project_id' => $riverside->id,
            'skill_name' => null,
            'skill_type' => null,
            'required_count' => 10,
            'is_need_certificate' => false,
        ]);
        $this->seedVolunteers($riverside, [$riversideGeneral], $clients, $admins);
        $this->seedDonations($riverside, $clients, $admins, [
            [6, 2500], [7, 15000], [8, 4000],
        ]);

        // 7) Donation-focused project spanning all four citizenship tiers.
        $well = Project::create([
            'user_id' => $owner(6),
            'name' => 'New Water Well Project',
            'description' => 'Drilling and equipping a new well to serve the eastern district.',
            'type' => 'municipal',
            'budget' => 5000000,
            'is_votable' => false,
            'is_voluntary' => false,
            'is_donation' => true,
            'status' => ProjectStatus::Approved->value,
        ]);
        $this->seedDonations($well, $clients, $admins, [
            [0, 1500], [1, 25000], [2, 350000], [3, 2500000], [4, 8000],
        ]);

        // 8) Votable, submitted, but the voting deadline already passed.
        $beautify = Project::create([
            'user_id' => $owner(7),
            'name' => 'Downtown Beautification',
            'description' => 'Murals, planters, and seating along the downtown pedestrian street.',
            'type' => 'municipal',
            'budget' => 20000,
            'is_votable' => true,
            'is_voluntary' => false,
            'is_donation' => false,
            'status' => ProjectStatus::Submitted->value,
            'voting_ends_at' => now()->subDays(3),
        ]);
        $this->castVotes($beautify, [[2, true], [5, false]], $clients);

        // 9) Votable, submitted, force-closed by an admin (with audit log entry).
        $flood = Project::create([
            'user_id' => $owner(8),
            'name' => 'Emergency Flood Relief Fund',
            'description' => 'Rapid-response fund for households affected by seasonal flooding.',
            'type' => 'municipal',
            'is_votable' => true,
            'is_voluntary' => false,
            'is_donation' => false,
            'status' => ProjectStatus::Submitted->value,
            'voting_closed_at' => now()->subDay(),
        ]);
        $this->castVotes($flood, [[1, true], [3, true], [6, true]], $clients);
        AuditLog::create([
            'user_id' => $superAdmin->id,
            'auditable_type' => Project::class,
            'auditable_id' => $flood->id,
            'action' => AuditAction::Close->value,
        ]);

        // 10) Rejected project.
        $overpass = Project::create([
            'user_id' => $owner(9),
            'name' => 'Downtown Overpass Proposal',
            'description' => 'Proposed vehicle overpass connecting downtown to the industrial zone.',
            'type' => 'municipal',
            'budget' => 900000,
            'is_votable' => false,
            'is_voluntary' => false,
            'is_donation' => false,
            'status' => ProjectStatus::Rejected->value,
            'rejection_reason' => 'Budget exceeds this fiscal year\'s municipal allocation.',
        ]);

        // 11) Still in planning (draft, never submitted).
        Project::create([
            'user_id' => $owner(10),
            'name' => 'Neighborhood Playground Upgrade',
            'description' => 'Draft proposal - not yet submitted for review.',
            'type' => 'community',
            'is_votable' => false,
            'is_voluntary' => true,
            'is_donation' => false,
            'status' => ProjectStatus::Planning->value,
        ]);

        // 12) Completed, historical.
        Project::create([
            'user_id' => $owner(11),
            'name' => 'Old Town Square Renovation',
            'description' => 'Completed last year: new paving, lighting, and public seating.',
            'type' => 'municipal',
            'budget' => 35000,
            'is_votable' => false,
            'is_voluntary' => false,
            'is_donation' => false,
            'status' => ProjectStatus::Completed->value,
            'start_date' => now()->subYear(),
            'end_date' => now()->subMonths(6),
        ]);

        // 13) Cancelled.
        Project::create([
            'user_id' => $owner(12),
            'name' => 'Cancelled Riverside Bridge',
            'description' => 'Cancelled due to a change in the district development plan.',
            'type' => 'municipal',
            'is_votable' => false,
            'is_voluntary' => false,
            'is_donation' => false,
            'status' => ProjectStatus::Cancelled->value,
        ]);
    }

    /**
     * @param array<int, array{0:int,1:bool}> $voterIndexesAndValues [clientIndex, value]
     */
    private function castVotes(Project $project, array $voterIndexesAndValues, $clients): void
    {
        foreach ($voterIndexesAndValues as [$clientIndex, $value]) {
            $voter = $clients[$clientIndex % $clients->count()];
            $score = (int) Profile::where('user_id', $voter->id)->value('citizenship_score');

            ProjectVote::create([
                'project_id' => $project->id,
                'user_id' => $voter->id,
                'value' => $value,
                'citizenship_score_at_vote_time' => $score,
                'vote_weight' => $this->voteWeight($score),
            ]);
        }
    }

    /**
     * @param ProjectRequirement[] $requirements
     */
    private function seedVolunteers(Project $project, array $requirements, $clients, $admins): void
    {
        $admin = $admins->first();
        $applicantIndex = 0;

        foreach ($requirements as $requirement) {
            $applicantsForThisRequirement = min($requirement->required_count + 2, 6);

            for ($i = 0; $i < $applicantsForThisRequirement; $i++) {
                $applicant = $clients[$applicantIndex % $clients->count()];
                $applicantIndex++;

                if ($requirement->skill_type !== null) {
                    UserSkill::firstOrCreate(
                        ['user_id' => $applicant->id, 'type' => $requirement->skill_type],
                        ['name' => $requirement->skill_name]
                    );
                }

                if (ProjectParticipant::where('project_id', $project->id)->where('user_id', $applicant->id)->exists()) {
                    continue;
                }

                // Fill capacity exactly, then leave a couple pending, and reject one.
                $approvedSoFar = ProjectParticipant::where('project_requirement_id', $requirement->id)
                    ->where('status', 'approved')->count();

                if ($approvedSoFar < $requirement->required_count) {
                    $status = 'approved';
                } elseif ($i === $applicantsForThisRequirement - 1) {
                    $status = 'rejected';
                } else {
                    $status = 'pending';
                }

                ProjectParticipant::create([
                    'project_id' => $project->id,
                    'user_id' => $applicant->id,
                    'role' => 'volunteer',
                    'status' => $status,
                    'whatsapp_number' => '09912345' . str_pad((string) $applicant->id, 2, '0', STR_PAD_LEFT),
                    'project_requirement_id' => $requirement->id,
                    'approved_by' => $status === 'approved' ? $admin->id : null,
                    'approved_at' => $status === 'approved' ? now()->subDays(2) : null,
                    'rejected_by' => $status === 'rejected' ? $admin->id : null,
                    'rejected_at' => $status === 'rejected' ? now()->subDay() : null,
                ]);
            }
        }
    }

    /**
     * @param array<int, array{0:int,1:float}> $donorIndexesAndAmounts [clientIndex, amount]
     */
    private function seedDonations(Project $project, $clients, $admins, array $donorIndexesAndAmounts): void
    {
        $admin = $admins->first();

        foreach ($donorIndexesAndAmounts as [$clientIndex, $amount]) {
            Donation::create([
                'project_id' => $project->id,
                'user_id' => $clients[$clientIndex % $clients->count()]->id,
                'payment' => $amount,
                'recorded_by' => $admin->id,
            ]);
        }
    }
}

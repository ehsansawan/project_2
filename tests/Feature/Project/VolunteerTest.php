<?php

namespace Tests\Feature\Project;

use App\Enums\SkillType;
use App\Models\Project;
use App\Models\ProjectParticipant;
use App\Models\UserCertificate;
use App\Models\UserSkill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesUsers;
use Tests\TestCase;

class VolunteerTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesUsers;

    private function openVoluntaryProject(array $attributes = []): Project
    {
        return Project::factory()->voluntary()->approved()->create($attributes);
    }

    public function test_eligible_user_can_apply_to_the_general_slot(): void
    {
        $user = $this->makeUser();
        $this->makeProfile($user);
        [, $headers] = $this->actingAsApi($user, ['project.applyVolunteer']);

        $project = $this->openVoluntaryProject();
        $project->requirements()->create(['skill_name' => null, 'skill_type' => null, 'required_count' => 5]);

        $response = $this->postJson("/api/project/volunteer/{$project->id}", ['whatsapp_number' => '0991234567'], $headers);

        $response->assertStatus(201);
        $this->assertDatabaseHas('project_participants', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_without_required_skill_cannot_apply_when_no_general_slot_exists(): void
    {
        $user = $this->makeUser();
        $this->makeProfile($user);
        [, $headers] = $this->actingAsApi($user, ['project.applyVolunteer']);

        $project = $this->openVoluntaryProject();
        $project->requirements()->create(['skill_name' => 'Electrical', 'skill_type' => SkillType::Technical->value, 'required_count' => 2]);

        $response = $this->postJson("/api/project/volunteer/{$project->id}", ['whatsapp_number' => '0991234567'], $headers);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('project_participants', ['project_id' => $project->id, 'user_id' => $user->id]);
    }

    public function test_user_with_skill_but_without_required_certificate_cannot_apply(): void
    {
        $user = $this->makeUser();
        $this->makeProfile($user);
        [, $headers] = $this->actingAsApi($user, ['project.applyVolunteer']);
        UserSkill::create(['user_id' => $user->id, 'name' => 'Electrical Wiring', 'type' => SkillType::Technical->value]);

        $project = $this->openVoluntaryProject();
        $project->requirements()->create([
            'skill_name' => 'Electrical', 'skill_type' => SkillType::Technical->value,
            'required_count' => 2, 'is_need_certificate' => true,
        ]);

        $response = $this->postJson("/api/project/volunteer/{$project->id}", ['whatsapp_number' => '0991234567'], $headers);

        $response->assertStatus(422);
    }

    public function test_user_with_skill_and_required_certificate_can_apply(): void
    {
        $user = $this->makeUser();
        $this->makeProfile($user);
        [, $headers] = $this->actingAsApi($user, ['project.applyVolunteer']);
        $skill = UserSkill::create(['user_id' => $user->id, 'name' => 'Electrical Wiring', 'type' => SkillType::Technical->value]);
        UserCertificate::create(['user_skill_id' => $skill->id, 'file_path' => 'certs/a.pdf', 'status' => 'approved']);

        $project = $this->openVoluntaryProject();
        $requirement = $project->requirements()->create([
            'skill_name' => 'Electrical', 'skill_type' => SkillType::Technical->value,
            'required_count' => 2, 'is_need_certificate' => true,
        ]);

        $response = $this->postJson("/api/project/volunteer/{$project->id}", ['whatsapp_number' => '0991234567'], $headers);

        $response->assertStatus(201);
        $this->assertDatabaseHas('project_participants', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_requirement_id' => $requirement->id,
        ]);
    }

    public function test_duplicate_application_is_rejected(): void
    {
        $user = $this->makeUser();
        $this->makeProfile($user);
        [, $headers] = $this->actingAsApi($user, ['project.applyVolunteer']);

        $project = $this->openVoluntaryProject();
        $project->requirements()->create(['skill_name' => null, 'skill_type' => null, 'required_count' => 5]);

        $this->postJson("/api/project/volunteer/{$project->id}", ['whatsapp_number' => '0991234567'], $headers)->assertStatus(201);
        $response = $this->postJson("/api/project/volunteer/{$project->id}", ['whatsapp_number' => '0991234567'], $headers);

        $response->assertStatus(409);
    }

    public function test_admin_can_approve_a_pending_application(): void
    {
        $applicant = $this->makeUser();
        $this->makeProfile($applicant);
        [, $applicantHeaders] = $this->actingAsApi($applicant, ['project.applyVolunteer']);

        $project = $this->openVoluntaryProject();
        $project->requirements()->create(['skill_name' => null, 'skill_type' => null, 'required_count' => 5]);
        $this->postJson("/api/project/volunteer/{$project->id}", ['whatsapp_number' => '0991234567'], $applicantHeaders)->assertStatus(201);

        $participant = ProjectParticipant::where('project_id', $project->id)->where('user_id', $applicant->id)->first();

        $admin = $this->makeUser();
        [, $adminHeaders] = $this->actingAsApi($admin, ['project.approveVolunteerApplication']);

        $response = $this->postJson("/api/admin/project/volunteer-applications/{$participant->id}/approve", [], $adminHeaders);

        $response->assertStatus(200);
        $this->assertDatabaseHas('project_participants', [
            'id' => $participant->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);
    }

    public function test_unauthorized_user_cannot_approve_an_application(): void
    {
        $applicant = $this->makeUser();
        $this->makeProfile($applicant);
        [, $applicantHeaders] = $this->actingAsApi($applicant, ['project.applyVolunteer']);

        $project = $this->openVoluntaryProject();
        $project->requirements()->create(['skill_name' => null, 'skill_type' => null, 'required_count' => 5]);
        $this->postJson("/api/project/volunteer/{$project->id}", ['whatsapp_number' => '0991234567'], $applicantHeaders)->assertStatus(201);

        $participant = ProjectParticipant::where('project_id', $project->id)->where('user_id', $applicant->id)->first();

        $randomUser = $this->makeUser();
        [, $randomHeaders] = $this->actingAsApi($randomUser, []);

        $response = $this->postJson("/api/admin/project/volunteer-applications/{$participant->id}/approve", [], $randomHeaders);

        $response->assertStatus(403);
    }

    public function test_admin_can_reject_a_pending_application(): void
    {
        $applicant = $this->makeUser();
        $this->makeProfile($applicant);
        [, $applicantHeaders] = $this->actingAsApi($applicant, ['project.applyVolunteer']);

        $project = $this->openVoluntaryProject();
        $project->requirements()->create(['skill_name' => null, 'skill_type' => null, 'required_count' => 5]);
        $this->postJson("/api/project/volunteer/{$project->id}", ['whatsapp_number' => '0991234567'], $applicantHeaders)->assertStatus(201);

        $participant = ProjectParticipant::where('project_id', $project->id)->where('user_id', $applicant->id)->first();

        $admin = $this->makeUser();
        [, $adminHeaders] = $this->actingAsApi($admin, ['project.rejectVolunteerApplication']);

        $response = $this->postJson("/api/admin/project/volunteer-applications/{$participant->id}/reject", [], $adminHeaders);

        $response->assertStatus(200);
        $this->assertDatabaseHas('project_participants', [
            'id' => $participant->id,
            'status' => 'rejected',
            'rejected_by' => $admin->id,
        ]);
    }

    public function test_unauthorized_user_cannot_reject_an_application(): void
    {
        $applicant = $this->makeUser();
        $this->makeProfile($applicant);
        [, $applicantHeaders] = $this->actingAsApi($applicant, ['project.applyVolunteer']);

        $project = $this->openVoluntaryProject();
        $project->requirements()->create(['skill_name' => null, 'skill_type' => null, 'required_count' => 5]);
        $this->postJson("/api/project/volunteer/{$project->id}", ['whatsapp_number' => '0991234567'], $applicantHeaders)->assertStatus(201);

        $participant = ProjectParticipant::where('project_id', $project->id)->where('user_id', $applicant->id)->first();

        $randomUser = $this->makeUser();
        [, $randomHeaders] = $this->actingAsApi($randomUser, []);

        $response = $this->postJson("/api/admin/project/volunteer-applications/{$participant->id}/reject", [], $randomHeaders);

        $response->assertStatus(403);
    }

    public function test_skill_specific_capacity_cannot_be_exceeded_on_approval(): void
    {
        $project = $this->openVoluntaryProject();
        $requirement = $project->requirements()->create([
            'skill_name' => 'Electrical', 'skill_type' => SkillType::Technical->value, 'required_count' => 1,
        ]);

        $admin = $this->makeUser();
        [, $adminHeaders] = $this->actingAsApi($admin, ['project.approveVolunteerApplication']);

        $participants = [];
        foreach (range(1, 2) as $i) {
            $applicant = $this->makeUser();
            $this->makeProfile($applicant);
            UserSkill::create(['user_id' => $applicant->id, 'name' => 'Wiring', 'type' => SkillType::Technical->value]);
            [, $applicantHeaders] = $this->actingAsApi($applicant, ['project.applyVolunteer']);

            $this->postJson("/api/project/volunteer/{$project->id}", ['whatsapp_number' => '099000000' . $i], $applicantHeaders)
                ->assertStatus(201);

            $participants[] = ProjectParticipant::where('project_id', $project->id)->where('user_id', $applicant->id)->first();
        }

        $this->postJson("/api/admin/project/volunteer-applications/{$participants[0]->id}/approve", [], $adminHeaders)
            ->assertStatus(200);

        $response = $this->postJson("/api/admin/project/volunteer-applications/{$participants[1]->id}/approve", [], $adminHeaders);

        $response->assertStatus(409);
        $this->assertSame(1, ProjectParticipant::where('project_requirement_id', $requirement->id)->where('status', 'approved')->count());
    }

    public function test_general_capacity_cannot_be_exceeded_on_approval(): void
    {
        $project = $this->openVoluntaryProject();
        $requirement = $project->requirements()->create([
            'skill_name' => null, 'skill_type' => null, 'required_count' => 1,
        ]);

        $admin = $this->makeUser();
        [, $adminHeaders] = $this->actingAsApi($admin, ['project.approveVolunteerApplication']);

        $participants = [];
        foreach (range(1, 2) as $i) {
            $applicant = $this->makeUser();
            $this->makeProfile($applicant);
            [, $applicantHeaders] = $this->actingAsApi($applicant, ['project.applyVolunteer']);

            $this->postJson("/api/project/volunteer/{$project->id}", ['whatsapp_number' => '099000000' . $i], $applicantHeaders)
                ->assertStatus(201);

            $participants[] = ProjectParticipant::where('project_id', $project->id)->where('user_id', $applicant->id)->first();
        }

        $this->postJson("/api/admin/project/volunteer-applications/{$participants[0]->id}/approve", [], $adminHeaders)
            ->assertStatus(200);

        $response = $this->postJson("/api/admin/project/volunteer-applications/{$participants[1]->id}/approve", [], $adminHeaders);

        $response->assertStatus(409);
        $this->assertSame(1, ProjectParticipant::where('project_requirement_id', $requirement->id)->where('status', 'approved')->count());
    }

    public function test_cannot_apply_before_the_volunteering_period_starts(): void
    {
        $user = $this->makeUser();
        $this->makeProfile($user);
        [, $headers] = $this->actingAsApi($user, ['project.applyVolunteer']);

        $project = $this->openVoluntaryProject(['start_date' => now()->addDays(3)->toDateString()]);
        $project->requirements()->create(['skill_name' => null, 'skill_type' => null, 'required_count' => 5]);

        $response = $this->postJson("/api/project/volunteer/{$project->id}", ['whatsapp_number' => '0991234567'], $headers);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('project_participants', ['project_id' => $project->id, 'user_id' => $user->id]);
    }

    public function test_cannot_apply_after_the_volunteering_period_ends(): void
    {
        $user = $this->makeUser();
        $this->makeProfile($user);
        [, $headers] = $this->actingAsApi($user, ['project.applyVolunteer']);

        $project = $this->openVoluntaryProject(['end_date' => now()->subDay()->toDateString()]);
        $project->requirements()->create(['skill_name' => null, 'skill_type' => null, 'required_count' => 5]);

        $response = $this->postJson("/api/project/volunteer/{$project->id}", ['whatsapp_number' => '0991234567'], $headers);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('project_participants', ['project_id' => $project->id, 'user_id' => $user->id]);
    }

    public function test_can_apply_within_the_volunteering_period(): void
    {
        $user = $this->makeUser();
        $this->makeProfile($user);
        [, $headers] = $this->actingAsApi($user, ['project.applyVolunteer']);

        $project = $this->openVoluntaryProject([
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
        ]);
        $project->requirements()->create(['skill_name' => null, 'skill_type' => null, 'required_count' => 5]);

        $response = $this->postJson("/api/project/volunteer/{$project->id}", ['whatsapp_number' => '0991234567'], $headers);

        $response->assertStatus(201);
    }

    public function test_admin_can_list_volunteer_applications_for_a_project(): void
    {
        $applicant = $this->makeUser();
        $this->makeProfile($applicant);
        [, $applicantHeaders] = $this->actingAsApi($applicant, ['project.applyVolunteer']);

        $project = $this->openVoluntaryProject();
        $project->requirements()->create(['skill_name' => null, 'skill_type' => null, 'required_count' => 5]);
        $this->postJson("/api/project/volunteer/{$project->id}", ['whatsapp_number' => '0991234567'], $applicantHeaders)->assertStatus(201);

        $admin = $this->makeUser();
        [, $adminHeaders] = $this->actingAsApi($admin, ['project.listVolunteerApplications']);

        $response = $this->getJson("/api/admin/project/{$project->id}/volunteer-applications", $adminHeaders);

        $response->assertStatus(200);
        $rows = $response->json('data.data');
        $this->assertCount(1, $rows);
        $this->assertSame($applicant->id, $rows[0]['user_id']);
        $this->assertSame('pending', $rows[0]['status']);
    }

    public function test_volunteer_application_listing_can_be_filtered_by_status(): void
    {
        $applicant = $this->makeUser();
        $this->makeProfile($applicant);
        [, $applicantHeaders] = $this->actingAsApi($applicant, ['project.applyVolunteer']);

        $project = $this->openVoluntaryProject();
        $project->requirements()->create(['skill_name' => null, 'skill_type' => null, 'required_count' => 5]);
        $this->postJson("/api/project/volunteer/{$project->id}", ['whatsapp_number' => '0991234567'], $applicantHeaders)->assertStatus(201);

        $admin = $this->makeUser();
        [, $adminHeaders] = $this->actingAsApi($admin, ['project.listVolunteerApplications']);

        $response = $this->getJson("/api/admin/project/{$project->id}/volunteer-applications?status=rejected", $adminHeaders);

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data.data'));
    }

    public function test_project_details_expose_required_and_approved_volunteer_counts(): void
    {
        $applicant = $this->makeUser();
        $this->makeProfile($applicant);
        [, $applicantHeaders] = $this->actingAsApi($applicant, ['project.applyVolunteer']);

        $project = $this->openVoluntaryProject();
        $requirement = $project->requirements()->create(['skill_name' => null, 'skill_type' => null, 'required_count' => 5]);
        $this->postJson("/api/project/volunteer/{$project->id}", ['whatsapp_number' => '0991234567'], $applicantHeaders)->assertStatus(201);

        $participant = ProjectParticipant::where('project_id', $project->id)->where('user_id', $applicant->id)->first();

        $admin = $this->makeUser();
        [, $adminHeaders] = $this->actingAsApi($admin, ['project.approveVolunteerApplication']);
        $this->postJson("/api/admin/project/volunteer-applications/{$participant->id}/approve", [], $adminHeaders)->assertStatus(200);

        [, $viewerHeaders] = $this->actingAsApi($this->makeUser(), ['project.show']);
        $response = $this->getJson("/api/project/{$project->id}", $viewerHeaders);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertSame(5, $data['total_required_volunteers']);
        $this->assertSame(1, $data['total_approved_volunteers']);

        $requirementRow = collect($data['requirements'])->firstWhere('id', $requirement->id);
        $this->assertSame(5, $requirementRow['required_count']);
        $this->assertSame(1, $requirementRow['approved_count']);
        $this->assertSame(4, $requirementRow['remaining_count']);
    }
}

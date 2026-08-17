<?php

namespace Tests\Feature\Profile;

use App\Models\Donation;
use App\Models\Project;
use App\Models\ProjectParticipant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesUsers;
use Tests\TestCase;

class ProfileStatsTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesUsers;

    public function test_volunteering_count_only_counts_approved_applications(): void
    {
        $user = $this->makeUser();
        $this->makeProfile($user);
        [, $headers] = $this->actingAsApi($user, ['profile.index']);

        $approvedProject = Project::factory()->create();
        $pendingProject = Project::factory()->create();
        $rejectedProject = Project::factory()->create();

        ProjectParticipant::create([
            'project_id' => $approvedProject->id, 'user_id' => $user->id,
            'role' => 'volunteer', 'status' => 'approved',
        ]);
        ProjectParticipant::create([
            'project_id' => $pendingProject->id, 'user_id' => $user->id,
            'role' => 'volunteer', 'status' => 'pending',
        ]);
        ProjectParticipant::create([
            'project_id' => $rejectedProject->id, 'user_id' => $user->id,
            'role' => 'volunteer', 'status' => 'rejected',
        ]);

        $response = $this->getJson('/api/profile/', $headers);

        $response->assertStatus(200);
        $this->assertSame(1, $response->json('data.volunteering_count'));
    }

    public function test_total_donated_and_donation_count_are_calculated_correctly(): void
    {
        $user = $this->makeUser();
        $this->makeProfile($user);
        [, $headers] = $this->actingAsApi($user, ['profile.index']);

        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();

        Donation::create(['project_id' => $projectA->id, 'user_id' => $user->id, 'payment' => 100]);
        Donation::create(['project_id' => $projectB->id, 'user_id' => $user->id, 'payment' => 250]);

        $response = $this->getJson('/api/profile/', $headers);

        $response->assertStatus(200);
        $this->assertEqualsWithDelta(350.0, $response->json('data.total_donated'), 0.001);
        $this->assertSame(2, $response->json('data.donation_count'));
    }

    public function test_profile_with_no_activity_reports_zero_stats(): void
    {
        $user = $this->makeUser();
        $this->makeProfile($user);
        [, $headers] = $this->actingAsApi($user, ['profile.index']);

        $response = $this->getJson('/api/profile/', $headers);

        $response->assertStatus(200);
        $this->assertSame(0, $response->json('data.volunteering_count'));
        $this->assertEqualsWithDelta(0.0, $response->json('data.total_donated'), 0.001);
        $this->assertSame(0, $response->json('data.donation_count'));
    }
}

<?php

namespace Tests\Feature\Project;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesUsers;
use Tests\TestCase;

class DonationTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesUsers;

    private function donatableProject(array $attributes = []): Project
    {
        return Project::factory()->donatable()->approved()->create($attributes);
    }

    public function test_admin_can_record_a_donation(): void
    {
        $project = $this->donatableProject(['budget' => 10000]);
        $donor = $this->makeUser();
        $admin = $this->makeUser();
        [, $adminHeaders] = $this->actingAsApi($admin, ['project.recordDonation']);

        $response = $this->postJson("/api/admin/project/{$project->id}/donations", [
            'donor_user_id' => $donor->id,
            'amount' => 2500,
        ], $adminHeaders);

        $response->assertStatus(201);
        $this->assertDatabaseHas('donations', [
            'project_id' => $project->id,
            'user_id' => $donor->id,
            'payment' => 2500,
            'recorded_by' => $admin->id,
        ]);
        // recorded_by must stay the raw admin id in the response, not get
        // silently replaced by a loaded relation of the same serialized key.
        $this->assertSame($admin->id, $response->json('data.recorded_by'));
    }

    public function test_normal_user_cannot_record_a_donation(): void
    {
        $project = $this->donatableProject();
        $donor = $this->makeUser();
        $user = $this->makeUser();
        [, $headers] = $this->actingAsApi($user, []);

        $response = $this->postJson("/api/admin/project/{$project->id}/donations", [
            'donor_user_id' => $donor->id,
            'amount' => 2500,
        ], $headers);

        $response->assertStatus(403);
    }

    public function test_non_donatable_project_cannot_receive_a_donation(): void
    {
        $project = Project::factory()->approved()->create(['is_donation' => false]);
        $donor = $this->makeUser();
        $admin = $this->makeUser();
        [, $adminHeaders] = $this->actingAsApi($admin, ['project.recordDonation']);

        $response = $this->postJson("/api/admin/project/{$project->id}/donations", [
            'donor_user_id' => $donor->id,
            'amount' => 2500,
        ], $adminHeaders);

        $response->assertStatus(422);
    }

    public function test_invalid_amount_is_rejected(): void
    {
        $project = $this->donatableProject();
        $donor = $this->makeUser();
        $admin = $this->makeUser();
        [, $adminHeaders] = $this->actingAsApi($admin, ['project.recordDonation']);

        $response = $this->postJson("/api/admin/project/{$project->id}/donations", [
            'donor_user_id' => $donor->id,
            'amount' => 0,
        ], $adminHeaders);

        $response->assertStatus(422);
    }

    public function test_donation_below_the_minimum_amount_is_rejected(): void
    {
        $project = $this->donatableProject();
        $donor = $this->makeUser();
        $admin = $this->makeUser();
        [, $adminHeaders] = $this->actingAsApi($admin, ['project.recordDonation']);

        $response = $this->postJson("/api/admin/project/{$project->id}/donations", [
            'donor_user_id' => $donor->id,
            'amount' => 999,
        ], $adminHeaders);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('donations', ['project_id' => $project->id, 'user_id' => $donor->id]);
    }

    public function test_donation_statistics_are_calculated_correctly(): void
    {
        $project = $this->donatableProject(['budget' => 10000]);
        $donorA = $this->makeUser();
        $donorB = $this->makeUser();
        $admin = $this->makeUser();
        [, $adminHeaders] = $this->actingAsApi($admin, ['project.recordDonation', 'project.donationStats']);

        $this->postJson("/api/admin/project/{$project->id}/donations", ['donor_user_id' => $donorA->id, 'amount' => 3000], $adminHeaders)->assertStatus(201);
        $this->postJson("/api/admin/project/{$project->id}/donations", ['donor_user_id' => $donorB->id, 'amount' => 4000], $adminHeaders)->assertStatus(201);

        $response = $this->getJson("/api/project/{$project->id}/donations/stats", $adminHeaders);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEqualsWithDelta(7000.0, $data['total_donated'], 0.001);
        $this->assertEqualsWithDelta(10000.0, $data['donation_target'], 0.001);
        $this->assertEqualsWithDelta(3000.0, $data['remaining_amount'], 0.001);
        $this->assertEqualsWithDelta(70.0, $data['donation_percentage'], 0.001);
        $this->assertSame(2, $data['number_of_donors']);
    }

    public function test_donation_percentage_handles_zero_target_without_division_by_zero(): void
    {
        $project = $this->donatableProject(['budget' => 0]);
        $donor = $this->makeUser();
        $admin = $this->makeUser();
        [, $adminHeaders] = $this->actingAsApi($admin, ['project.recordDonation', 'project.donationStats']);

        $this->postJson("/api/admin/project/{$project->id}/donations", ['donor_user_id' => $donor->id, 'amount' => 1000], $adminHeaders)->assertStatus(201);

        $response = $this->getJson("/api/project/{$project->id}/donations/stats", $adminHeaders);

        $response->assertStatus(200);
        $this->assertEqualsWithDelta(0.0, $response->json('data.donation_percentage'), 0.001);
    }

    public function test_top_donors_are_ranked_by_total_donated_with_correct_counts(): void
    {
        $project = $this->donatableProject();
        $donorA = $this->makeUser();
        $donorB = $this->makeUser();
        $admin = $this->makeUser();
        [, $adminHeaders] = $this->actingAsApi($admin, ['project.recordDonation', 'project.topDonors']);

        $this->postJson("/api/admin/project/{$project->id}/donations", ['donor_user_id' => $donorA->id, 'amount' => 2000], $adminHeaders)->assertStatus(201);
        $this->postJson("/api/admin/project/{$project->id}/donations", ['donor_user_id' => $donorA->id, 'amount' => 1500], $adminHeaders)->assertStatus(201);
        $this->postJson("/api/admin/project/{$project->id}/donations", ['donor_user_id' => $donorB->id, 'amount' => 5000], $adminHeaders)->assertStatus(201);

        $response = $this->getJson("/api/project/{$project->id}/donations/top-donors", $adminHeaders);

        $response->assertStatus(200);
        $donors = $response->json('data');

        $this->assertSame($donorB->id, $donors[0]['user_id']);
        $this->assertEqualsWithDelta(5000.0, $donors[0]['total_donated'], 0.001);
        $this->assertSame(1, $donors[0]['donation_count']);

        $this->assertSame($donorA->id, $donors[1]['user_id']);
        $this->assertEqualsWithDelta(3500.0, $donors[1]['total_donated'], 0.001);
        $this->assertSame(2, $donors[1]['donation_count']);
    }

    public function test_donation_records_can_be_listed_for_a_project(): void
    {
        $project = $this->donatableProject();
        $donorA = $this->makeUser();
        $admin = $this->makeUser();
        [, $adminHeaders] = $this->actingAsApi($admin, ['project.recordDonation', 'project.listDonations']);

        $this->postJson("/api/admin/project/{$project->id}/donations", ['donor_user_id' => $donorA->id, 'amount' => 1000], $adminHeaders)->assertStatus(201);

        $response = $this->getJson("/api/project/{$project->id}/donations", $adminHeaders);

        $response->assertStatus(200);
        $rows = $response->json('data.data');
        $this->assertCount(1, $rows);
        $this->assertSame($donorA->id, $rows[0]['user_id']);
        $this->assertEquals(1000, $rows[0]['payment']);
        $this->assertSame($admin->id, $rows[0]['recorded_by']);
    }

    public function test_recording_a_donation_increases_the_donors_citizenship_score(): void
    {
        $project = $this->donatableProject();
        $donor = $this->makeUser();
        $this->makeProfile($donor, 50);
        $admin = $this->makeUser();
        [, $adminHeaders] = $this->actingAsApi($admin, ['project.recordDonation']);

        // Falls in the 1,000-10,000 tier.
        $this->postJson("/api/admin/project/{$project->id}/donations", ['donor_user_id' => $donor->id, 'amount' => 5000], $adminHeaders)->assertStatus(201);

        $score = $donor->profile()->first()->citizenship_score;
        $this->assertGreaterThan(50, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    public function test_a_larger_donation_tier_grants_a_bigger_or_equal_citizenship_boost(): void
    {
        $project = $this->donatableProject();
        $admin = $this->makeUser();
        [, $adminHeaders] = $this->actingAsApi($admin, ['project.recordDonation']);

        $smallDonor = $this->makeUser();
        $this->makeProfile($smallDonor, 50);
        $this->postJson("/api/admin/project/{$project->id}/donations", ['donor_user_id' => $smallDonor->id, 'amount' => 1000], $adminHeaders)->assertStatus(201);
        $smallGain = (float) $smallDonor->profile()->first()->citizenship_score - 50;

        $largeDonor = $this->makeUser();
        $this->makeProfile($largeDonor, 50);
        $this->postJson("/api/admin/project/{$project->id}/donations", ['donor_user_id' => $largeDonor->id, 'amount' => 2000000], $adminHeaders)->assertStatus(201);
        $largeGain = (float) $largeDonor->profile()->first()->citizenship_score - 50;

        $this->assertGreaterThan(0, $smallGain);
        $this->assertGreaterThan($smallGain, $largeGain);
    }

    public function test_recording_a_donation_notifies_the_donor(): void
    {
        $project = $this->donatableProject(['name' => 'Clean Water Fund']);
        $donor = $this->makeUser();
        $admin = $this->makeUser();
        [, $adminHeaders] = $this->actingAsApi($admin, ['project.recordDonation']);

        $this->postJson("/api/admin/project/{$project->id}/donations", ['donor_user_id' => $donor->id, 'amount' => 5000], $adminHeaders)->assertStatus(201);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $donor->id,
            'title' => 'Donation Recorded',
        ]);
    }
}

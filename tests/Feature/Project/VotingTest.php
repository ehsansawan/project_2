<?php

namespace Tests\Feature\Project;

use App\Models\Project;
use App\Models\ProjectVote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesUsers;
use Tests\TestCase;

class VotingTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesUsers;

    private function votableSubmittedProject(array $attributes = []): Project
    {
        return Project::factory()->votable()->submitted()->create($attributes);
    }

    public function test_user_can_vote_on_a_votable_submitted_project(): void
    {
        $user = $this->makeUser();
        $this->makeProfile($user, 50);
        [, $headers] = $this->actingAsApi($user, ['project.vote']);

        $project = $this->votableSubmittedProject();

        $response = $this->postJson("/api/project/vote/{$project->id}", ['value' => true], $headers);

        $response->assertStatus(201);
        $this->assertDatabaseHas('project_votes', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'value' => 1,
        ]);
    }

    public function test_user_cannot_vote_twice_on_the_same_project(): void
    {
        $user = $this->makeUser();
        $this->makeProfile($user, 50);
        [, $headers] = $this->actingAsApi($user, ['project.vote']);

        $project = $this->votableSubmittedProject();

        $this->postJson("/api/project/vote/{$project->id}", ['value' => true], $headers)->assertStatus(201);
        $response = $this->postJson("/api/project/vote/{$project->id}", ['value' => true], $headers);

        $response->assertStatus(409);
        $this->assertSame(1, ProjectVote::where('project_id', $project->id)->where('user_id', $user->id)->count());
    }

    public function test_vote_weight_is_calculated_as_one_plus_sqrt_of_citizenship_score(): void
    {
        $user = $this->makeUser();
        $this->makeProfile($user, 100);
        [, $headers] = $this->actingAsApi($user, ['project.vote']);

        $project = $this->votableSubmittedProject();

        $this->postJson("/api/project/vote/{$project->id}", ['value' => true], $headers)->assertStatus(201);

        $vote = ProjectVote::where('project_id', $project->id)->where('user_id', $user->id)->first();

        $this->assertEqualsWithDelta(6.0, (float) $vote->vote_weight, 0.0001);
        $this->assertSame(100, $vote->citizenship_score_at_vote_time);
    }

    public function test_vote_weight_snapshot_is_not_affected_by_a_later_citizenship_score_change(): void
    {
        $user = $this->makeUser();
        $profile = $this->makeProfile($user, 25);
        [, $headers] = $this->actingAsApi($user, ['project.vote']);

        $project = $this->votableSubmittedProject();

        $this->postJson("/api/project/vote/{$project->id}", ['value' => true], $headers)->assertStatus(201);

        // score changes after voting
        $profile->update(['citizenship_score' => 90]);

        $vote = ProjectVote::where('project_id', $project->id)->where('user_id', $user->id)->first();

        $this->assertSame(25, $vote->citizenship_score_at_vote_time);
        $this->assertEqualsWithDelta(3.5, (float) $vote->vote_weight, 0.0001);
    }

    public function test_non_votable_project_cannot_receive_a_vote(): void
    {
        $user = $this->makeUser();
        $this->makeProfile($user, 50);
        [, $headers] = $this->actingAsApi($user, ['project.vote']);

        $project = Project::factory()->submitted()->create(['is_votable' => false]);

        $response = $this->postJson("/api/project/vote/{$project->id}", ['value' => true], $headers);

        $response->assertStatus(422);
    }

    public function test_project_not_in_submitted_status_cannot_receive_a_vote(): void
    {
        $user = $this->makeUser();
        $this->makeProfile($user, 50);
        [, $headers] = $this->actingAsApi($user, ['project.vote']);

        $project = Project::factory()->votable()->create();

        $response = $this->postJson("/api/project/vote/{$project->id}", ['value' => true], $headers);

        $response->assertStatus(422);
    }

    public function test_expired_voting_cannot_receive_a_vote(): void
    {
        $user = $this->makeUser();
        $this->makeProfile($user, 50);
        [, $headers] = $this->actingAsApi($user, ['project.vote']);

        $project = $this->votableSubmittedProject(['voting_ends_at' => now()->subDay()]);

        $response = $this->postJson("/api/project/vote/{$project->id}", ['value' => true], $headers);

        $response->assertStatus(422);
    }

    public function test_force_closed_voting_cannot_receive_a_vote(): void
    {
        $user = $this->makeUser();
        $this->makeProfile($user, 50);
        [, $headers] = $this->actingAsApi($user, ['project.vote']);

        $project = $this->votableSubmittedProject(['voting_closed_at' => now()->subHour()]);

        $response = $this->postJson("/api/project/vote/{$project->id}", ['value' => true], $headers);

        $response->assertStatus(422);
    }

    public function test_votable_listing_computes_weighted_approval_percentage_and_my_vote(): void
    {
        $voterA = $this->makeUser();
        $this->makeProfile($voterA, 100); // weight 6, votes yes
        $voterB = $this->makeUser();
        $this->makeProfile($voterB, 0); // weight 1, votes no

        [, $headersA] = $this->actingAsApi($voterA, ['project.vote']);
        [, $headersB] = $this->actingAsApi($voterB, ['project.vote']);

        $project = $this->votableSubmittedProject();

        $this->postJson("/api/project/vote/{$project->id}", ['value' => true], $headersA)->assertStatus(201);
        $this->postJson("/api/project/vote/{$project->id}", ['value' => false], $headersB)->assertStatus(201);

        [, $headersViewer] = $this->actingAsApi($voterA, ['project.votable']);

        $response = $this->getJson('/api/project/votable', $headersViewer);

        $response->assertStatus(200);
        $row = collect($response->json('data.data'))->firstWhere('id', $project->id);

        $this->assertNotNull($row);
        $this->assertSame(2, $row['total_votes']);
        $this->assertEqualsWithDelta(6.0, $row['weighted_yes_votes'], 0.0001);
        $this->assertEqualsWithDelta(1.0, $row['weighted_no_votes'], 0.0001);
        // 6 / (6 + 1) * 100
        $this->assertEqualsWithDelta(85.71, $row['approval_percentage'], 0.01);
        $this->assertTrue($row['has_voted']);
    }

    public function test_zero_vote_project_is_handled_without_division_by_zero(): void
    {
        $user = $this->makeUser();
        $this->makeProfile($user, 50);
        [, $headers] = $this->actingAsApi($user, ['project.votable']);

        $this->votableSubmittedProject();

        $response = $this->getJson('/api/project/votable', $headers);

        $response->assertStatus(200);
        $row = collect($response->json('data.data'))->first();

        $this->assertSame(0, $row['total_votes']);
        $this->assertSame(0, $row['approval_percentage']);
        $this->assertFalse($row['has_voted']);
    }

    public function test_votable_listing_is_ordered_by_weighted_approval_percentage_not_recency(): void
    {
        // Created first (oldest) but highest approval percentage -> must sort first.
        $highApproval = $this->votableSubmittedProject();
        $lowVoter = $this->makeUser();
        $this->makeProfile($lowVoter, 0); // weight 1
        [, $lowHeaders] = $this->actingAsApi($lowVoter, ['project.vote']);
        $this->postJson("/api/project/vote/{$highApproval->id}", ['value' => true], $lowHeaders)->assertStatus(201);

        // Created last (newest) but zero votes -> must sort last despite recency.
        $zeroVotes = $this->votableSubmittedProject();

        // Created in between, lower approval percentage than the first project.
        $mixed = $this->votableSubmittedProject();
        $yesVoter = $this->makeUser();
        $this->makeProfile($yesVoter, 0);
        $noVoter = $this->makeUser();
        $this->makeProfile($noVoter, 0);
        [, $yesHeaders] = $this->actingAsApi($yesVoter, ['project.vote']);
        [, $noHeaders] = $this->actingAsApi($noVoter, ['project.vote']);
        $this->postJson("/api/project/vote/{$mixed->id}", ['value' => true], $yesHeaders)->assertStatus(201);
        $this->postJson("/api/project/vote/{$mixed->id}", ['value' => false], $noHeaders)->assertStatus(201);

        $viewer = $this->makeUser();
        [, $viewerHeaders] = $this->actingAsApi($viewer, ['project.votable']);

        $response = $this->getJson('/api/project/votable', $viewerHeaders);

        $response->assertStatus(200);
        $ids = collect($response->json('data.data'))->pluck('id')->all();

        $this->assertSame([$highApproval->id, $mixed->id, $zeroVotes->id], $ids);
    }

    public function test_user_can_withdraw_their_own_vote(): void
    {
        $project = $this->votableSubmittedProject();
        $user = $this->makeUser();
        $this->makeProfile($user, 40);
        [, $headers] = $this->actingAsApi($user, ['project.vote', 'project.unvote']);

        $this->postJson("/api/project/vote/{$project->id}", ['value' => true], $headers)->assertStatus(201);
        $this->assertDatabaseHas('project_votes', ['project_id' => $project->id, 'user_id' => $user->id]);

        $response = $this->deleteJson("/api/project/vote/{$project->id}", [], $headers);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('project_votes', ['project_id' => $project->id, 'user_id' => $user->id]);
    }

    public function test_user_can_change_their_mind_by_unvoting_then_revoting(): void
    {
        $project = $this->votableSubmittedProject();
        $user = $this->makeUser();
        $this->makeProfile($user, 40);
        [, $headers] = $this->actingAsApi($user, ['project.vote', 'project.unvote']);

        $this->postJson("/api/project/vote/{$project->id}", ['value' => false], $headers)->assertStatus(201);
        $this->deleteJson("/api/project/vote/{$project->id}", [], $headers)->assertStatus(200);

        $response = $this->postJson("/api/project/vote/{$project->id}", ['value' => true], $headers);

        $response->assertStatus(201);
        $this->assertDatabaseHas('project_votes', ['project_id' => $project->id, 'user_id' => $user->id, 'value' => 1]);
    }

    public function test_cannot_unvote_a_project_the_user_never_voted_on(): void
    {
        $project = $this->votableSubmittedProject();
        $user = $this->makeUser();
        [, $headers] = $this->actingAsApi($user, ['project.unvote']);

        $response = $this->deleteJson("/api/project/vote/{$project->id}", [], $headers);

        $response->assertStatus(404);
    }

    public function test_cannot_unvote_after_voting_has_been_force_closed(): void
    {
        $project = $this->votableSubmittedProject();
        $user = $this->makeUser();
        $this->makeProfile($user, 40);
        [, $headers] = $this->actingAsApi($user, ['project.vote', 'project.unvote']);

        $this->postJson("/api/project/vote/{$project->id}", ['value' => true], $headers)->assertStatus(201);

        $project->update(['voting_closed_at' => now()]);

        $response = $this->deleteJson("/api/project/vote/{$project->id}", [], $headers);

        $response->assertStatus(422);
        $this->assertDatabaseHas('project_votes', ['project_id' => $project->id, 'user_id' => $user->id]);
    }

    public function test_client_can_view_voting_stats_for_a_project_still_open_for_voting(): void
    {
        $project = $this->votableSubmittedProject();
        $voter = $this->makeUser();
        $this->makeProfile($voter, 50);
        [, $voterHeaders] = $this->actingAsApi($voter, ['project.vote', 'project.votingStats']);

        $this->postJson("/api/project/vote/{$project->id}", ['value' => true], $voterHeaders)->assertStatus(201);

        $response = $this->getJson("/api/project/{$project->id}/voting/stats", $voterHeaders);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertSame(1, $data['total_votes']);
        $this->assertSame('active', $data['voting_status']);
        $this->assertTrue($data['has_voted']);
        $this->assertArrayNotHasKey('votes', $data); // no individual vote list for citizens
    }

    public function test_client_can_still_view_voting_stats_after_the_project_leaves_the_voting_stage(): void
    {
        $project = $this->votableSubmittedProject();
        $voter = $this->makeUser();
        $this->makeProfile($voter, 50);
        [, $voterHeaders] = $this->actingAsApi($voter, ['project.vote', 'project.votingStats']);

        $this->postJson("/api/project/vote/{$project->id}", ['value' => true], $voterHeaders)->assertStatus(201);

        $project->update(['status' => \App\Enums\ProjectStatus::Approved]);

        // No longer in the votable() listing (status != submitted)...
        $listResponse = $this->getJson('/api/project/votable', $voterHeaders);
        $this->assertNotContains($project->id, collect($listResponse->json('data.data'))->pluck('id')->all());

        // ...but the dedicated per-project stats endpoint still shows the final result.
        $response = $this->getJson("/api/project/{$project->id}/voting/stats", $voterHeaders);
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertSame(1, $data['total_votes']);
        $this->assertSame('concluded', $data['voting_status']);
    }

    public function test_admin_can_view_the_voting_overview(): void
    {
        $admin = $this->makeUser();
        [, $adminHeaders] = $this->actingAsApi($admin, ['project.votingOverview']);

        $active = $this->votableSubmittedProject();
        $planning = Project::factory()->votable()->create(['user_id' => $admin->id]);

        $voter = $this->makeUser();
        $this->makeProfile($voter, 50);
        [, $voterHeaders] = $this->actingAsApi($voter, ['project.vote']);
        $this->postJson("/api/project/vote/{$active->id}", ['value' => true], $voterHeaders)->assertStatus(201);

        $response = $this->getJson('/api/admin/project/voting/statistics', $adminHeaders);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertSame(2, $data['total_votable_projects']);
        $this->assertSame(1, $data['by_voting_status']['active']);
        $this->assertSame(1, $data['by_voting_status']['not_started']);
        $this->assertSame(1, $data['total_votes_cast']);
    }

    public function test_client_cannot_view_the_admin_voting_overview(): void
    {
        $user = $this->makeUser();
        [, $headers] = $this->actingAsApi($user, []);

        $response = $this->getJson('/api/admin/project/voting/statistics', $headers);

        $response->assertStatus(403);
    }

    public function test_admin_can_view_per_project_voting_statistics_with_individual_votes(): void
    {
        $project = $this->votableSubmittedProject();
        $voter = $this->makeUser();
        $this->makeProfile($voter, 80);
        [, $voterHeaders] = $this->actingAsApi($voter, ['project.vote']);
        $this->postJson("/api/project/vote/{$project->id}", ['value' => true], $voterHeaders)->assertStatus(201);

        $admin = $this->makeUser();
        [, $adminHeaders] = $this->actingAsApi($admin, ['project.votingStatistics']);

        $response = $this->getJson("/api/admin/project/{$project->id}/voting/statistics", $adminHeaders);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertSame(1, $data['total_votes']);
        $votes = $data['votes']['data'];
        $this->assertCount(1, $votes);
        $this->assertSame($voter->id, $votes[0]['user_id']);
        $this->assertSame(80, $votes[0]['citizenship_score_at_vote_time']);
    }

    public function test_client_cannot_view_per_project_admin_voting_statistics(): void
    {
        $project = $this->votableSubmittedProject();
        $user = $this->makeUser();
        [, $headers] = $this->actingAsApi($user, ['project.votingStats']);

        $response = $this->getJson("/api/admin/project/{$project->id}/voting/statistics", $headers);

        $response->assertStatus(403);
    }
}

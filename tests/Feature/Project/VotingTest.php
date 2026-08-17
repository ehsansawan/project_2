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

        $this->assertEqualsWithDelta(11.0, (float) $vote->vote_weight, 0.0001);
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
        $this->assertEqualsWithDelta(6.0, (float) $vote->vote_weight, 0.0001);
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
        $this->makeProfile($voterA, 100); // weight 11, votes yes
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
        $this->assertEqualsWithDelta(11.0, $row['weighted_yes_votes'], 0.0001);
        $this->assertEqualsWithDelta(1.0, $row['weighted_no_votes'], 0.0001);
        // 11 / (11 + 1) * 100
        $this->assertEqualsWithDelta(91.67, $row['approval_percentage'], 0.01);
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
}

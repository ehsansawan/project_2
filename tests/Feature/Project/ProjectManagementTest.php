<?php

namespace Tests\Feature\Project;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesUsers;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesUsers;

    public function test_admin_can_mark_a_project_as_votable_on_create(): void
    {
        $user = $this->makeUser();
        [, $headers] = $this->actingAsApi($user, ['project.create']);

        $response = $this->postJson('/api/project/create', [
            'name' => 'Community Park',
            'description' => 'A new park for the neighborhood',
            'is_votable' => true,
        ], $headers);

        $response->assertStatus(201);
        $this->assertDatabaseHas('projects', [
            'name' => 'Community Park',
            'is_votable' => 1,
        ]);
    }

    public function test_project_is_not_votable_by_default_when_omitted_on_create(): void
    {
        $user = $this->makeUser();
        [, $headers] = $this->actingAsApi($user, ['project.create']);

        $response = $this->postJson('/api/project/create', [
            'name' => 'Community Park',
            'description' => 'A new park for the neighborhood',
        ], $headers);

        $response->assertStatus(201);
        $this->assertDatabaseHas('projects', [
            'name' => 'Community Park',
            'is_votable' => 0,
        ]);
    }

    public function test_admin_can_toggle_is_votable_on_update_while_project_is_still_planning(): void
    {
        $user = $this->makeUser();
        [, $headers] = $this->actingAsApi($user, ['project.create', 'project.update']);

        $project = Project::factory()->create(['user_id' => $user->id, 'is_votable' => false]);

        $response = $this->postJson("/api/project/update/{$project->id}", ['is_votable' => true], $headers);

        $response->assertStatus(200);
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'is_votable' => 1]);
    }

    public function test_index_can_filter_projects_by_is_votable(): void
    {
        $user = $this->makeUser();
        [, $headers] = $this->actingAsApi($user, ['project.index']);

        $votable = Project::factory()->votable()->create(['user_id' => $user->id]);
        $nonVotable = Project::factory()->create(['user_id' => $user->id, 'is_votable' => false]);

        $response = $this->getJson('/api/project?is_votable=1', $headers);

        $response->assertStatus(200);
        $ids = collect($response->json('data.data'))->pluck('id')->all();

        $this->assertContains($votable->id, $ids);
        $this->assertNotContains($nonVotable->id, $ids);
    }

    public function test_index_can_filter_for_non_votable_projects_explicitly(): void
    {
        $user = $this->makeUser();
        [, $headers] = $this->actingAsApi($user, ['project.index']);

        $votable = Project::factory()->votable()->create(['user_id' => $user->id]);
        $nonVotable = Project::factory()->create(['user_id' => $user->id, 'is_votable' => false]);

        $response = $this->getJson('/api/project?is_votable=0', $headers);

        $response->assertStatus(200);
        $ids = collect($response->json('data.data'))->pluck('id')->all();

        $this->assertContains($nonVotable->id, $ids);
        $this->assertNotContains($votable->id, $ids);
    }

    public function test_index_without_is_votable_filter_returns_both(): void
    {
        $user = $this->makeUser();
        [, $headers] = $this->actingAsApi($user, ['project.index']);

        $votable = Project::factory()->votable()->create(['user_id' => $user->id]);
        $nonVotable = Project::factory()->create(['user_id' => $user->id, 'is_votable' => false]);

        $response = $this->getJson('/api/project', $headers);

        $response->assertStatus(200);
        $ids = collect($response->json('data.data'))->pluck('id')->all();

        $this->assertContains($votable->id, $ids);
        $this->assertContains($nonVotable->id, $ids);
    }

    public function test_pending_approval_splits_submitted_projects_by_votable_status(): void
    {
        $superAdmin = $this->makeUser();
        [, $headers] = $this->actingAsApi($superAdmin, ['project.pendingApproval']);

        $votable = Project::factory()->votable()->submitted()->create(['user_id' => $superAdmin->id]);
        $nonVotable = Project::factory()->submitted()->create(['user_id' => $superAdmin->id, 'is_votable' => false]);
        $stillPlanning = Project::factory()->votable()->create(['user_id' => $superAdmin->id]);

        $response = $this->getJson('/api/project/pending-approval', $headers);

        $response->assertStatus(200);
        $data = $response->json('data');

        $votableIds = collect($data['votable']['data'])->pluck('id')->all();
        $nonVotableIds = collect($data['non_votable']['data'])->pluck('id')->all();

        $this->assertContains($votable->id, $votableIds);
        $this->assertNotContains($nonVotable->id, $votableIds);

        $this->assertContains($nonVotable->id, $nonVotableIds);
        $this->assertNotContains($votable->id, $nonVotableIds);

        $this->assertNotContains($stillPlanning->id, $votableIds);
        $this->assertNotContains($stillPlanning->id, $nonVotableIds);
    }

    public function test_pending_approval_orders_votable_projects_by_vote_count_descending(): void
    {
        $superAdmin = $this->makeUser();
        [, $headers] = $this->actingAsApi($superAdmin, ['project.pendingApproval']);

        $fewVotes = Project::factory()->votable()->submitted()->create(['user_id' => $superAdmin->id]);
        $manyVotes = Project::factory()->votable()->submitted()->create(['user_id' => $superAdmin->id]);

        $oneVoter = $this->makeUser();
        $this->makeProfile($oneVoter);
        [, $oneVoterHeaders] = $this->actingAsApi($oneVoter, ['project.vote']);
        $this->postJson("/api/project/vote/{$fewVotes->id}", ['value' => true], $oneVoterHeaders)->assertStatus(201);

        foreach (range(1, 3) as $i) {
            $voter = $this->makeUser();
            $this->makeProfile($voter);
            [, $voterHeaders] = $this->actingAsApi($voter, ['project.vote']);
            $this->postJson("/api/project/vote/{$manyVotes->id}", ['value' => true], $voterHeaders)->assertStatus(201);
        }

        $response = $this->getJson('/api/project/pending-approval', $headers);

        $response->assertStatus(200);
        $ids = collect($response->json('data.votable.data'))->pluck('id')->all();

        $this->assertSame([$manyVotes->id, $fewVotes->id], array_values(array_intersect($ids, [$manyVotes->id, $fewVotes->id])));
    }

    public function test_regular_admin_cannot_view_the_pending_approval_queue(): void
    {
        $admin = $this->makeUser();
        [, $headers] = $this->actingAsApi($admin, ['project.index', 'project.show']); // typical admin permission set, no pendingApproval

        $response = $this->getJson('/api/project/pending-approval', $headers);

        $response->assertStatus(403);
    }
}

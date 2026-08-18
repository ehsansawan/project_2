<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\ProjectVote;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ProjectVoteService
{
    /**
     * Scales down how much citizenship_score can move the vote weight.
     * Lower value = flatter influence, i.e. citizens are closer to equal.
     */
    private const CITIZENSHIP_INFLUENCE_FACTOR = 0.5;

    /**
     * Weight formula: 1 + CITIZENSHIP_INFLUENCE_FACTOR * sqrt(max(citizenship_score, 0)).
     *
     * A flat +1 baseline guarantees every citizen's vote counts for at least
     * one unit. sqrt() grows sub-linearly so a high score can't dominate the
     * outcome, unlike a raw linear weight, and it degrades gracefully if
     * citizenship_score is ever left uncapped above 100 (it currently has no
     * hard ceiling elsewhere in the codebase, only a floor at 0).
     *
     * The influence factor further tones down the spread between a score of
     * 0 and 100: at 1.0 that spread was 1 -> 11 (11x); at 0.5 it's 1 -> 6.
     */
    public function calculateWeight(int $citizenshipScore): float
    {
        return 1 + (self::CITIZENSHIP_INFLUENCE_FACTOR * sqrt(max($citizenshipScore, 0)));
    }

    public function vote($request): array
    {
        $user = auth('api')->user();
        $project = Project::query()->find($request['id']);

        if (!$project) {
            return ['data' => null, 'message' => 'project not found', 'code' => 404];
        }

        if ($blocked = $this->votingWindowError($project)) {
            return $blocked;
        }

        if (ProjectVote::query()->where('project_id', $project->id)->where('user_id', $user->id)->exists()) {
            return ['data' => null, 'message' => 'you have already voted on this project', 'code' => 409];
        }

        $citizenshipScore = (int) ($user->profile->citizenship_score ?? 0);
        $weight = $this->calculateWeight($citizenshipScore);

        try {
            $vote = DB::transaction(function () use ($project, $user, $request, $citizenshipScore, $weight) {
                return ProjectVote::query()->create([
                    'project_id' => $project->id,
                    'user_id' => $user->id,
                    'value' => (bool) $request['value'],
                    'citizenship_score_at_vote_time' => $citizenshipScore,
                    'vote_weight' => $weight,
                ]);
            });
        } catch (QueryException $e) {
            return ['data' => null, 'message' => 'you have already voted on this project', 'code' => 409];
        }

        return ['data' => $vote, 'message' => 'vote recorded successfully', 'code' => 201];
    }

    /**
     * Lets a user withdraw their own vote - e.g. they voted by mistake or
     * changed their mind. Only allowed while voting is still active, same
     * window as casting a vote in the first place; to switch a yes/no choice,
     * unvote then vote again with the new value.
     */
    public function unvote($request): array
    {
        $user = auth('api')->user();
        $project = Project::query()->find($request['id']);

        if (!$project) {
            return ['data' => null, 'message' => 'project not found', 'code' => 404];
        }

        if ($blocked = $this->votingWindowError($project)) {
            return $blocked;
        }

        $vote = ProjectVote::query()->where('project_id', $project->id)->where('user_id', $user->id)->first();

        if (!$vote) {
            return ['data' => null, 'message' => 'you have not voted on this project', 'code' => 404];
        }

        $vote->delete();

        return ['data' => null, 'message' => 'vote removed successfully', 'code' => 200];
    }

    /**
     * Shared eligibility checks for both vote() and unvote(): returns an
     * error response array if voting isn't currently open, null otherwise.
     */
    private function votingWindowError(Project $project): ?array
    {
        if (!$project->is_votable) {
            return ['data' => null, 'message' => 'this project is not open for voting', 'code' => 422];
        }

        if ($project->status !== ProjectStatus::Submitted) {
            return ['data' => null, 'message' => 'this project is not currently in the voting stage', 'code' => 422];
        }

        if ($project->voting_closed_at !== null) {
            return ['data' => null, 'message' => 'voting on this project has been closed by an administrator', 'code' => 422];
        }

        if ($project->voting_ends_at !== null && now()->greaterThanOrEqualTo($project->voting_ends_at)) {
            return ['data' => null, 'message' => 'the voting period for this project has ended', 'code' => 422];
        }

        return null;
    }

    /**
     * Aggregate voting stats for a single project, regardless of its status -
     * unlike listVotable() (which only covers currently-open, is_votable +
     * submitted projects), this lets a citizen check the final result of a
     * project whose voting stage has already ended. No individual vote list
     * here (that's admin-only, see AdminProjectService::votingStatistics()) -
     * just aggregates plus the caller's own vote, same shape as listVotable().
     */
    public function projectStats($request): array
    {
        $project = $this->withVoteAggregates(Project::query())->find($request['id']);

        if (!$project) {
            return ['data' => null, 'message' => 'project not found', 'code' => 404];
        }

        $userId = auth('api')->id();
        $myVote = $userId
            ? ProjectVote::query()->where('project_id', $project->id)->where('user_id', $userId)->first()
            : null;

        return [
            'data' => $this->formatVoteStats($project, $myVote),
            'message' => 'voting statistics retrieved successfully',
            'code' => 200,
        ];
    }

    /**
     * Adds weighted yes/no sums and a total vote count to a Project query,
     * shared by listVotable(), projectStats() and the admin voting endpoints.
     */
    public function withVoteAggregates($query)
    {
        return $query
            ->withSum(['votes as weighted_yes_votes' => function ($q) {
                $q->where('value', true);
            }], 'vote_weight')
            ->withSum(['votes as weighted_no_votes' => function ($q) {
                $q->where('value', false);
            }], 'vote_weight')
            ->withCount('votes as total_votes');
    }

    /**
     * Shared response shape for a single project's vote aggregates.
     */
    public function formatVoteStats(Project $project, ?ProjectVote $myVote): array
    {
        $yes = (float) ($project->weighted_yes_votes ?? 0);
        $no = (float) ($project->weighted_no_votes ?? 0);
        $totalWeighted = $yes + $no;

        return [
            'project_id' => $project->id,
            'is_votable' => $project->is_votable,
            'voting_status' => $project->voting_status,
            'voting_ends_at' => $project->voting_ends_at,
            'voting_closed_at' => $project->voting_closed_at,
            'total_votes' => $project->total_votes,
            'weighted_yes_votes' => $yes,
            'weighted_no_votes' => $no,
            'total_weighted_votes' => $totalWeighted,
            'approval_percentage' => $totalWeighted > 0 ? round(($yes / $totalWeighted) * 100, 2) : 0,
            'has_voted' => $myVote !== null,
            'my_vote' => $myVote,
        ];
    }

    public function listVotable($request): array
    {
        $query = $this->withVoteAggregates(Project::query()->votable()->with('user.profile'));

        // Ordered by weighted approval percentage, not raw vote count. Zero-vote
        // projects (no denominator) are pushed to the end via the -1 sentinel,
        // avoiding a division-by-zero in SQL.
        $query->orderByRaw('
            CASE
                WHEN (COALESCE(weighted_yes_votes, 0) + COALESCE(weighted_no_votes, 0)) > 0
                THEN COALESCE(weighted_yes_votes, 0) / (COALESCE(weighted_yes_votes, 0) + COALESCE(weighted_no_votes, 0))
                ELSE -1
            END DESC
        ')->latest();

        $projects = $query->paginate(15);

        $userId = auth('api')->id();

        $myVotesByProject = $userId
            ? ProjectVote::query()
                ->where('user_id', $userId)
                ->whereIn('project_id', $projects->getCollection()->pluck('id'))
                ->get()
                ->keyBy('project_id')
            : collect();

        $projects->getCollection()->transform(function (Project $project) use ($myVotesByProject) {
            $yes = (float) ($project->weighted_yes_votes ?? 0);
            $no = (float) ($project->weighted_no_votes ?? 0);
            $totalWeighted = $yes + $no;

            $myVote = $myVotesByProject->get($project->id);

            return [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'user' => $project->user,
                'voting_status' => $project->voting_status,
                'voting_ends_at' => $project->voting_ends_at,
                'total_votes' => $project->total_votes,
                'weighted_yes_votes' => $yes,
                'weighted_no_votes' => $no,
                'total_weighted_votes' => $totalWeighted,
                'approval_percentage' => $totalWeighted > 0 ? round(($yes / $totalWeighted) * 100, 2) : 0,
                'has_voted' => $myVote !== null,
                'my_vote' => $myVote,
            ];
        });

        return ['data' => $projects, 'message' => 'votable projects retrieved successfully', 'code' => 200];
    }
}

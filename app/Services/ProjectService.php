<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Traits\PictureTrait;
use Illuminate\Http\UploadedFile;

class ProjectService
{
    use PictureTrait;

    public function store($request): array
    {
        $user = auth('api')->user();

        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => $request['name'],
            'description' => $request['description'],
            'type' => 'municipal',
            'is_voluntary' => $request['requires_volunteers'] ?? false,
            'is_donation' => $request['requires_donations'] ?? false,
            'is_votable' => $request['is_votable'] ?? false,
            'latitude' => $request['latitude'] ?? null,
            'longitude' => $request['longitude'] ?? null,
            'budget' => $request['budget'] ?? null,
            'voting_ends_at' => $request['voting_ends_at'] ?? null,
            'status' => 'planning',
        ]);

        if (!$project) {
            return ['data' => $project, 'message' => 'something went wrong, try again later', 'code' => 500];
        }

        $this->attachMedia($project, $request['media'] ?? []);

        if (!empty($request['requirements'])) {
            $this->syncRequirements($project, $request['requirements']);
        }

        $project->load(['user.profile', 'media', 'requirements' => $this->requirementsConstraint()]);

        return ['data' => $this->formatProject($project), 'message' => 'project created successfully', 'code' => 201];
    }
    public function index($request): array
    {
        $query = Project::query()->with(['user.profile', 'requirements' => $this->requirementsConstraint()]);

        if (!empty($request['statuses'])) {
            $query->whereIn('status', $request['statuses']);
        }

        if (array_key_exists('is_votable', $request) && $request['is_votable'] !== null) {
            $query->where('is_votable', filter_var($request['is_votable'], FILTER_VALIDATE_BOOLEAN));
        }

        $projects = $query->latest()->paginate(15);

        $projects->getCollection()->transform(fn ($project) => $this->formatProject($project));

        return ['data' => $projects, 'message' => 'projects retrieved successfully', 'code' => 200];
    }
    public function publicIndex($request): array
    {
        $projects = Project::query()->public()
            ->with(['user.profile', 'requirements' => $this->requirementsConstraint()])
            ->latest()->paginate(15);

        $projects->getCollection()->transform(fn ($project) => $this->formatProject($project));

        return ['data' => $projects, 'message' => 'projects retrieved successfully', 'code' => 200];
    }

    public function show($request): array
    {
        $project = Project::query()->with(['user.profile', 'requirements' => $this->requirementsConstraint()])->find($request['id']);

        if (!$project) {
            return ['data' => null, 'message' => 'project not found', 'code' => 404];
        }

        return ['data' => $this->formatProject($project), 'message' => 'project retrieved successfully', 'code' => 200];
    }
    public function update($request): array
    {
        $user = auth('api')->user();
        $project = Project::query()->find($request['id']);

        if (!$project) {
            return ['data' => null, 'message' => 'project not found', 'code' => 404];
        }

        if ($project->user_id !== $user->id && !$user->hasRole('super-admin')) {
            return ['data' => null, 'message' => 'you can only update your own project', 'code' => 403];
        }

        if ($project->status !== ProjectStatus::Planning) {
            return ['data' => null, 'message' => 'only planning projects can be updated', 'code' => 422];
        }

        $data = [
            'name' => $request['name'] ?? $project->name,
            'description' => $request['description'] ?? $project->description,
            'is_voluntary' => $request['requires_volunteers'] ?? $project->is_voluntary,
            'is_donation' => $request['requires_donations'] ?? $project->is_donation,
            'is_votable' => $request['is_votable'] ?? $project->is_votable,
            'latitude' => $request['latitude'] ?? $project->latitude,
            'longitude' => $request['longitude'] ?? $project->longitude,
            'budget' => $request['budget'] ?? $project->budget,
            'voting_ends_at' => $request['voting_ends_at'] ?? $project->voting_ends_at,
        ];

        $project->update($data);

        if (!empty($request['media'])) {
            $this->replaceMedia($project, $request['media']);
        }

        if (array_key_exists('requirements', $request) && is_array($request['requirements'])) {
            $this->syncRequirements($project, $request['requirements']);
        }

        $project->refresh();
        $project->load(['user.profile', 'media', 'requirements' => $this->requirementsConstraint()]);

        return ['data' => $this->formatProject($project), 'message' => 'project updated successfully', 'code' => 200];
    }
    public function destroy($request): array
    {
        $user = auth('api')->user();
        $project = Project::query()->find($request['id']);

        if (!$project) {
            return ['data' => null, 'message' => 'project not found', 'code' => 404];
        }

        if ($project->user_id !== $user->id && !$user->hasRole('super-admin')) {
            return ['data' => null, 'message' => 'you can only delete your own project', 'code' => 403];
        }

        foreach ($project->media as $media) {
            $this->destroyPicture($media->file_path);
        }

        $project->media()->delete();

        $project->delete();

        return ['data' => null, 'message' => 'project deleted successfully', 'code' => 200];
    }
    public function submitForReview($request): array
    {
        $user = auth('api')->user();
        $project = Project::query()->find($request['id']);

        if (!$project) {
            return ['data' => null, 'message' => 'project not found', 'code' => 404];
        }



        if ($project->status !== ProjectStatus::Planning) {
            return ['data' => null, 'message' => 'only planning projects can be submitted for review', 'code' => 422];
        }

        $project->update(['status' => ProjectStatus::Submitted]);

        $project->load(['user.profile', 'media', 'requirements' => $this->requirementsConstraint()]);

        return ['data' => $this->formatProject($project), 'message' => 'project submitted for review successfully', 'code' => 200];
    }
    /**
     * Super-admin review queue: submitted projects awaiting an approve/reject
     * decision, split into votable vs. non-votable. Votable ones are ordered
     * by raw vote count (how many people voted), highest first - distinct
     * from ProjectVoteService::listVotable()'s weighted-approval-percentage
     * ordering, which is about ranking by public support, not review priority.
     */
    public function pendingApproval($request): array
    {
        $votable = Project::query()
            ->where('status', ProjectStatus::Submitted)
            ->where('is_votable', true)
            ->withCount('votes as total_votes')
            ->with(['user.profile', 'requirements' => $this->requirementsConstraint()])
            ->orderByDesc('total_votes')
            ->latest()
            ->paginate(15, ['*'], 'votable_page');

        $votable->getCollection()->transform(fn ($project) => $this->formatProject($project));

        $nonVotable = Project::query()
            ->where('status', ProjectStatus::Submitted)
            ->where('is_votable', false)
            ->with(['user.profile', 'requirements' => $this->requirementsConstraint()])
            ->latest()
            ->paginate(15, ['*'], 'non_votable_page');

        $nonVotable->getCollection()->transform(fn ($project) => $this->formatProject($project));

        return [
            'data' => [
                'votable' => $votable,
                'non_votable' => $nonVotable,
            ],
            'message' => 'pending projects retrieved successfully',
            'code' => 200,
        ];
    }

    public function approve($request): array
    {
        $project = Project::query()->find($request['id']);

        if (!$project) {
            return ['data' => null, 'message' => 'project not found', 'code' => 404];
        }

        if ($project->status !== ProjectStatus::Submitted) {
            return ['data' => null, 'message' => 'only submitted projects can be approved', 'code' => 422];
        }

        $project->update([
            'status' => ProjectStatus::Approved,
            'rejection_reason' => null,
        ]);

        $project->load(['user.profile', 'media', 'requirements' => $this->requirementsConstraint()]);

        return ['data' => $this->formatProject($project), 'message' => 'project approved successfully', 'code' => 200];
    }
    public function reject($request): array
    {
        $project = Project::query()->find($request['id']);

        if (!$project) {
            return ['data' => null, 'message' => 'project not found', 'code' => 404];
        }

        if ($project->status !== ProjectStatus::Submitted) {
            return ['data' => null, 'message' => 'only submitted projects can be rejected', 'code' => 422];
        }

        $project->update([
            'status' => ProjectStatus::Rejected,
            'rejection_reason' => $request['reason'] ?? null,
        ]);

        $project->load(['user.profile', 'media', 'requirements' => $this->requirementsConstraint()]);

        return ['data' => $this->formatProject($project), 'message' => 'project rejected successfully', 'code' => 200];
    }
    private function attachMedia(Project $project, array $media): void
    {
        foreach ($media as $file) {
            $path = $this->storePicture($file, 'uploads/projects');

            $project->media()->create([
                'file_path' => $path,
                'media_type' => $this->resolveMediaType($file),
            ]);
        }
    }
    private function replaceMedia(Project $project, array $media): void
    {
        foreach ($project->media as $oldMedia) {
            $this->destroyPicture($oldMedia->file_path);
        }

        $project->media()->delete();

        $this->attachMedia($project, $media);
    }
    private function resolveMediaType(UploadedFile $file): string
    {
        $mime = $file->getMimeType();

        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }

        return 'document';
    }

    /**
     * Constrains the eager-loaded `requirements` relation to also count each
     * requirement's currently-approved volunteers, so formatProject() can expose
     * fill/remaining counts without triggering a query per requirement.
     */
    private function requirementsConstraint(): \Closure
    {
        return function ($query) {
            $query->withCount(['participants as approved_count' => function ($q) {
                $q->where('status', 'approved');
            }]);
        };
    }

    private function formatProject(Project $project): Project
    {
        $project->setRelation('media', $project->media->map(function ($media) {
            return [
                'id' => $media->id,
                'file_path' => $media->file_path,
                'media_type' => $media->media_type,
                'file_url' => asset('storage/' . $media->file_path),
            ];
        }));

        if ($project->relationLoaded('requirements')) {
            $totalRequired = 0;
            $totalApproved = 0;

            $project->setRelation('requirements', $project->requirements->map(function ($requirement) use (&$totalRequired, &$totalApproved) {
                $approvedCount = $requirement->approved_count ?? 0;
                $totalRequired += $requirement->required_count;
                $totalApproved += $approvedCount;

                return [
                    'id' => $requirement->id,
                    'skill_name' => $requirement->skill_name,
                    'skill_type' => $requirement->skill_type,
                    'required_count' => $requirement->required_count,
                    'is_need_certificate' => $requirement->is_need_certificate,
                    'approved_count' => $approvedCount,
                    'remaining_count' => max($requirement->required_count - $approvedCount, 0),
                ];
            }));

            $project->total_required_volunteers = $totalRequired;
            $project->total_approved_volunteers = $totalApproved;
        }

        return $project;
    }

    private function syncRequirements(Project $project, array $requirements): void
    {
        $project->requirements()->delete();

        foreach ($requirements as $requirement) {
            $project->requirements()->create([
                'skill_name' => $requirement['skill_name'] ?? null,
                'skill_type' => $requirement['skill_type'] ?? null,
                'required_count' => $requirement['required_count'],
                'is_need_certificate' => $requirement['is_need_certificate'] ?? false,
            ]);
        }
    }
}

<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Traits\PictureTrait;

class ProjectService
{
    use PictureTrait;

    public function store($request): array
    {
        $user = auth('api')->user();

        $imageUrl = null;
        if (!empty($request['image'])) {
            $imageUrl = $this->storePicture($request['image'], 'uploads/projects');
        }

        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => $request['name'],
            'description' => $request['description'],
            'type' => 'municipal',
            'is_voluntary' => $request['requires_volunteers'] ?? false,
            'is_donation' => $request['requires_donations'] ?? false,
            'image_url' => $imageUrl,
            'latitude' => $request['latitude'] ?? null,
            'longitude' => $request['longitude'] ?? null,
            'status' => 'planning',
        ]);

        if (!$project) {
            return ['data' => $project, 'message' => 'something went wrong, try again later', 'code' => 500];
        }

        return ['data' => $project, 'message' => 'project created successfully', 'code' => 201];
    }
    public function index($request): array
    {
        $query = Project::query()->with('user.profile');

        if (!empty($request['statuses'])) {
            $query->whereIn('status', $request['statuses']);
        }

        $projects = $query->latest()->paginate(15);

        return ['data' => $projects, 'message' => 'projects retrieved successfully', 'code' => 200];
    }
    public function show($request): array
    {
        $project = Project::query()->with('user.profile')->find($request['id']);

        if (!$project) {
            return ['data' => null, 'message' => 'project not found', 'code' => 404];
        }

        return ['data' => $project, 'message' => 'project retrieved successfully', 'code' => 200];
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

        $imageUrl = $project->image_url;
        if (!empty($request['image'])) {
            $imageUrl = $this->updatePicture($request['image'], $project->image_url, 'uploads/projects');
        }

        $data = [
            'name' => $request['name'] ?? $project->name,
            'description' => $request['description'] ?? $project->description,
            'is_voluntary' => $request['requires_volunteers'] ?? $project->is_voluntary,
            'is_donation' => $request['requires_donations'] ?? $project->is_donation,
            'image_url' => $imageUrl,
            'latitude' => $request['latitude'] ?? $project->latitude,
            'longitude' => $request['longitude'] ?? $project->longitude,
        ];

        $project->update($data);

        $project->refresh();

        return ['data' => $project, 'message' => 'project updated successfully', 'code' => 200];
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

        if ($project->image_url) {
            $this->destroyPicture($project->image_url);
        }

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

        return ['data' => $project, 'message' => 'project submitted for review successfully', 'code' => 200];
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

        return ['data' => $project, 'message' => 'project approved successfully', 'code' => 200];
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

        return ['data' => $project, 'message' => 'project rejected successfully', 'code' => 200];
    }
}

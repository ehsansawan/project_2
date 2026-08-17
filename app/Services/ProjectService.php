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
            'latitude' => $request['latitude'] ?? null,
            'longitude' => $request['longitude'] ?? null,
            'status' => 'planning',
        ]);

        if (!$project) {
            return ['data' => $project, 'message' => 'something went wrong, try again later', 'code' => 500];
        }

        $this->attachMedia($project, $request['media'] ?? []);

        $project->load('user.profile', 'media');

        return ['data' => $this->formatProject($project), 'message' => 'project created successfully', 'code' => 201];
    }
    public function index($request): array
    {
        $query = Project::query()->with('user.profile');

        if (!empty($request['statuses'])) {
            $query->whereIn('status', $request['statuses']);
        }

        $projects = $query->latest()->paginate(15);

        $projects->getCollection()->transform(fn ($project) => $this->formatProject($project));

        return ['data' => $projects, 'message' => 'projects retrieved successfully', 'code' => 200];
    }
    public function show($request): array
    {
        $project = Project::query()->with('user.profile')->find($request['id']);

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
            'latitude' => $request['latitude'] ?? $project->latitude,
            'longitude' => $request['longitude'] ?? $project->longitude,
        ];

        $project->update($data);

        if (!empty($request['media'])) {
            $this->replaceMedia($project, $request['media']);
        }

        $project->refresh();
        $project->load('user.profile', 'media');

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

        return ['data' => $this->formatProject($project->load('user.profile', 'media')), 'message' => 'project submitted for review successfully', 'code' => 200];
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

        return ['data' => $this->formatProject($project->load('user.profile', 'media')), 'message' => 'project approved successfully', 'code' => 200];
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

        return ['data' => $this->formatProject($project->load('user.profile', 'media')), 'message' => 'project rejected successfully', 'code' => 200];
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

        return $project;
    }
}

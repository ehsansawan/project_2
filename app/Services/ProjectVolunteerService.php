<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\ProjectParticipant;
use App\Models\UserCertificate;
use App\Models\UserSkill;

class ProjectVolunteerService
{
    private const OPEN_STATUSES = [
        ProjectStatus::Approved,
        ProjectStatus::Open,
        ProjectStatus::Active,
    ];

    public function apply($request): array
    {
        $user = auth('api')->user();
        $project = Project::query()->with('requirements')->find($request['id']);

        if (!$project) {
            return ['data' => null, 'message' => 'project not found', 'code' => 404];
        }

        if (!$project->is_voluntary) {
            return ['data' => null, 'message' => 'this project does not accept volunteers', 'code' => 422];
        }

        if (!in_array($project->status, self::OPEN_STATUSES, true)) {
            return ['data' => null, 'message' => 'volunteering is not currently open for this project', 'code' => 422];
        }

        if ($project->start_date !== null && now()->lessThan($project->start_date->copy()->startOfDay())) {
            return ['data' => null, 'message' => 'the volunteering period for this project has not started yet', 'code' => 422];
        }

        if ($project->end_date !== null && now()->greaterThan($project->end_date->copy()->endOfDay())) {
            return ['data' => null, 'message' => 'the volunteering period for this project has ended', 'code' => 422];
        }

        if (ProjectParticipant::query()->where('project_id', $project->id)->where('user_id', $user->id)->exists()) {
            return ['data' => null, 'message' => 'you have already applied to volunteer for this project', 'code' => 409];
        }

        if ($project->requirements->isEmpty()) {
            return ['data' => null, 'message' => 'volunteering is not configured for this project yet', 'code' => 422];
        }

        $requirement = $this->matchRequirement($project, $user);

        if (!$requirement) {
            return ['data' => null, 'message' => 'no suitable volunteer slot is available for your skills', 'code' => 422];
        }

        $participant = ProjectParticipant::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'volunteer',
            'status' => 'pending',
            'whatsapp_number' => $request['whatsapp_number'],
            'project_requirement_id' => $requirement->id,
        ]);

        return ['data' => $participant, 'message' => 'volunteer application submitted successfully', 'code' => 201];
    }

    private function matchRequirement(Project $project, $user)
    {
        $userSkillTypes = UserSkill::query()->where('user_id', $user->id)->pluck('type')->unique();

        $skillSpecific = $project->requirements->whereNotNull('skill_type')->sortBy('id');
        $general = $project->requirements->whereNull('skill_type')->sortBy('id')->first();

        foreach ($skillSpecific as $requirement) {
            if (!$userSkillTypes->contains($requirement->skill_type)) {
                continue;
            }

            if ($requirement->is_need_certificate && !$this->hasApprovedCertificate($user, $requirement->skill_type)) {
                continue;
            }

            if ($this->remainingCapacity($requirement) > 0) {
                return $requirement;
            }
        }

        if ($general && $this->remainingCapacity($general) > 0) {
            return $general;
        }

        return null;
    }

    private function hasApprovedCertificate($user, string $skillType): bool
    {
        return UserCertificate::query()
            ->where('status', 'approved')
            ->whereHas('userSkill', function ($query) use ($user, $skillType) {
                $query->where('user_id', $user->id)->where('type', $skillType);
            })
            ->exists();
    }

    private function remainingCapacity($requirement): int
    {
        $approvedCount = ProjectParticipant::query()
            ->where('project_requirement_id', $requirement->id)
            ->where('status', 'approved')
            ->count();

        return $requirement->required_count - $approvedCount;
    }
}

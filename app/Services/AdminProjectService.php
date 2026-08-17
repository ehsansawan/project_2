<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ProjectParticipant;
use App\Models\ProjectRequirement;
use App\Models\UserCertificate;
use App\Models\UserSkill;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminProjectService
{
    public function listVolunteerApplications($request): array
    {
        $project = Project::query()->find($request['id']);

        if (!$project) {
            return ['data' => null, 'message' => 'project not found', 'code' => 404];
        }

        $query = ProjectParticipant::query()->where('project_id', $project->id)->with(['user.profile', 'requirement']);

        if (!empty($request['status'])) {
            $query->where('status', $request['status']);
        }

        $applications = $query->latest()->paginate(15);

        return ['data' => $applications, 'message' => 'volunteer applications retrieved successfully', 'code' => 200];
    }

    public function approveVolunteerApplication($request): array
    {
        $admin = auth('api')->user();

        if (!ProjectParticipant::query()->where('id', $request['id'])->exists()) {
            return ['data' => null, 'message' => 'volunteer application not found', 'code' => 404];
        }

        try {
            $participant = DB::transaction(function () use ($request, $admin) {
                $participant = ProjectParticipant::query()->lockForUpdate()->find($request['id']);

                if (!$participant) {
                    throw new RuntimeException('volunteer application not found', 404);
                }

                if ($participant->status !== 'pending') {
                    throw new RuntimeException('only pending applications can be approved', 422);
                }

                $requirement = $participant->project_requirement_id
                    ? ProjectRequirement::query()->lockForUpdate()->find($participant->project_requirement_id)
                    : null;

                if ($requirement) {
                    $approvedCount = ProjectParticipant::query()
                        ->where('project_requirement_id', $requirement->id)
                        ->where('status', 'approved')
                        ->count();

                    if ($approvedCount >= $requirement->required_count) {
                        throw new RuntimeException('this volunteer slot is already full', 409);
                    }

                    if ($requirement->skill_type !== null) {
                        $hasSkill = UserSkill::query()
                            ->where('user_id', $participant->user_id)
                            ->where('type', $requirement->skill_type)
                            ->exists();

                        if (!$hasSkill) {
                            throw new RuntimeException('applicant no longer has the required skill for this slot', 422);
                        }

                        if ($requirement->is_need_certificate) {
                            $hasCertificate = UserCertificate::query()
                                ->where('status', 'approved')
                                ->whereHas('userSkill', function ($query) use ($participant, $requirement) {
                                    $query->where('user_id', $participant->user_id)->where('type', $requirement->skill_type);
                                })
                                ->exists();

                            if (!$hasCertificate) {
                                throw new RuntimeException('applicant no longer has the required certificate for this slot', 422);
                            }
                        }
                    }
                }

                $participant->update([
                    'status' => 'approved',
                    'approved_by' => $admin->id,
                    'approved_at' => now(),
                ]);

                AuditLog::create([
                    'user_id' => $admin->id,
                    'auditable_type' => ProjectParticipant::class,
                    'auditable_id' => $participant->id,
                    'action' => AuditAction::Approve->value,
                ]);

                return $participant;
            });
        } catch (RuntimeException $e) {
            return ['data' => null, 'message' => $e->getMessage(), 'code' => $e->getCode()];
        }

        return ['data' => $participant->load('user.profile', 'requirement'), 'message' => 'volunteer application approved successfully', 'code' => 200];
    }

    public function rejectVolunteerApplication($request): array
    {
        $admin = auth('api')->user();
        $participant = ProjectParticipant::query()->find($request['id']);

        if (!$participant) {
            return ['data' => null, 'message' => 'volunteer application not found', 'code' => 404];
        }

        if ($participant->status !== 'pending') {
            return ['data' => null, 'message' => 'only pending applications can be rejected', 'code' => 422];
        }

        $participant->update([
            'status' => 'rejected',
            'rejected_by' => $admin->id,
            'rejected_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $admin->id,
            'auditable_type' => ProjectParticipant::class,
            'auditable_id' => $participant->id,
            'action' => AuditAction::Reject->value,
        ]);

        return ['data' => $participant->load('user.profile'), 'message' => 'volunteer application rejected successfully', 'code' => 200];
    }

    public function forceCloseVoting($request): array
    {
        $admin = auth('api')->user();
        $project = Project::query()->find($request['id']);

        if (!$project) {
            return ['data' => null, 'message' => 'project not found', 'code' => 404];
        }

        if (!$project->is_votable) {
            return ['data' => null, 'message' => 'this project is not votable', 'code' => 422];
        }

        if ($project->voting_closed_at !== null) {
            return ['data' => null, 'message' => 'voting is already closed for this project', 'code' => 422];
        }

        $project->update(['voting_closed_at' => now()]);

        AuditLog::create([
            'user_id' => $admin->id,
            'auditable_type' => Project::class,
            'auditable_id' => $project->id,
            'action' => AuditAction::Close->value,
        ]);

        return ['data' => $project, 'message' => 'voting closed successfully', 'code' => 200];
    }
}

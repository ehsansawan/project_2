<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Mail\VolunteerApplicationDecision;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ProjectParticipant;
use App\Models\ProjectRequirement;
use App\Models\ProjectVote;
use App\Models\UserCertificate;
use App\Models\UserSkill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class AdminProjectService
{
    private CitizenshipService $citizenshipService;
    private ProjectVoteService $projectVoteService;
    private FcmService $fcmService;

    public function __construct(
        CitizenshipService $citizenshipService,
        ProjectVoteService $projectVoteService,
        FcmService $fcmService
    ) {
        $this->citizenshipService = $citizenshipService;
        $this->projectVoteService = $projectVoteService;
        $this->fcmService = $fcmService;
    }

    /**
     * Per-project voting detail for admins/super-admins: the same aggregates
     * a citizen sees via ProjectController::votingStats(), plus the full
     * individual-vote audit trail (who voted, when, their citizenship score
     * snapshot and computed weight) - not exposed to citizens. Works for any
     * project status, so results remain visible after voting has concluded.
     */
    public function votingStatistics($request): array
    {
        $project = $this->projectVoteService->withVoteAggregates(Project::query())->find($request['id']);

        if (!$project) {
            return ['data' => null, 'message' => 'project not found', 'code' => 404];
        }

        $votes = ProjectVote::query()
            ->where('project_id', $project->id)
            ->with('user.profile')
            ->latest()
            ->paginate(15);

        $stats = $this->projectVoteService->formatVoteStats($project, null);
        unset($stats['has_voted'], $stats['my_vote']);
        $stats['project_name'] = $project->name;
        $stats['votes'] = $votes;

        return ['data' => $stats, 'message' => 'voting statistics retrieved successfully', 'code' => 200];
    }

    /**
     * Platform-wide voting overview across every votable project, for the
     * admin/super-admin dashboard.
     */
    public function votingOverview(): array
    {
        $projects = $this->projectVoteService->withVoteAggregates(Project::query()->where('is_votable', true))->get();

        $byVotingStatus = ['not_started' => 0, 'active' => 0, 'expired' => 0, 'force_closed' => 0, 'concluded' => 0];
        $totalVotes = 0;
        $totalWeightedYes = 0.0;
        $totalWeightedNo = 0.0;
        $percentages = [];

        foreach ($projects as $project) {
            $status = $project->voting_status;
            if (array_key_exists($status, $byVotingStatus)) {
                $byVotingStatus[$status]++;
            }

            $totalVotes += $project->total_votes;

            $yes = (float) ($project->weighted_yes_votes ?? 0);
            $no = (float) ($project->weighted_no_votes ?? 0);
            $totalWeightedYes += $yes;
            $totalWeightedNo += $no;

            $totalWeighted = $yes + $no;
            if ($totalWeighted > 0) {
                $percentages[] = ($yes / $totalWeighted) * 100;
            }
        }

        return [
            'data' => [
                'total_votable_projects' => $projects->count(),
                'by_voting_status' => $byVotingStatus,
                'total_votes_cast' => $totalVotes,
                'total_weighted_yes_votes' => round($totalWeightedYes, 4),
                'total_weighted_no_votes' => round($totalWeightedNo, 4),
                'average_approval_percentage' => count($percentages) > 0
                    ? round(array_sum($percentages) / count($percentages), 2)
                    : 0,
            ],
            'message' => 'voting overview retrieved successfully',
            'code' => 200,
        ];
    }
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

                // Citizenship boost only on acceptance, scaled by how many times
                // this user has been accepted as a volunteer so far (this one
                // included) - repeat volunteers get a bigger increase.
                $approvedVolunteeringCount = ProjectParticipant::query()
                    ->where('user_id', $participant->user_id)
                    ->where('status', 'approved')
                    ->count();

                $increaseAmount = $this->citizenshipService->volunteeringCountToIncreaseAmount($approvedVolunteeringCount);

                if ($increaseAmount > 0) {
                    $this->citizenshipService->increase($participant->user, $increaseAmount);
                }

                return $participant;
            });
        } catch (RuntimeException $e) {
            return ['data' => null, 'message' => $e->getMessage(), 'code' => $e->getCode()];
        }

        // Notify after the transaction has committed, mirroring
        // AdminVerificationService::Approve() - a notification/email hiccup
        // must never roll back an already-committed approval.
        $this->notifyVolunteerDecision($participant, 'approved');

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

        $this->notifyVolunteerDecision($participant, 'rejected');

        return ['data' => $participant->load('user.profile'), 'message' => 'volunteer application rejected successfully', 'code' => 200];
    }

    /**
     * Mirrors AdminVerificationService's approve/reject notification pattern
     * (push + stored in-app notification via FcmService) and additionally
     * emails the applicant, since a volunteer decision - unlike most admin
     * actions in this app - was specifically called out as needing an email.
     *
     * Runs after the approval/rejection has already been committed, so any
     * failure here (bad mail config, Firebase down) is swallowed and logged
     * rather than turning an already-successful decision into an API error.
     */
    private function notifyVolunteerDecision(ProjectParticipant $participant, string $status): void
    {
        try {
            $user = $participant->user;
            $projectName = $participant->project->name;

            $title = $status === 'approved' ? 'Volunteer Application Approved' : 'Volunteer Application Rejected';
            $body = $status === 'approved'
                ? "Your application to volunteer for \"{$projectName}\" has been approved."
                : "Your application to volunteer for \"{$projectName}\" was not accepted.";
            $data = [
                'type' => 'volunteer_application',
                'status' => $status,
                'project_id' => (string) $participant->project_id,
                'participant_id' => (string) $participant->id,
            ];

            $this->fcmService->sendToUser($user, $title, $body, $data);
            $this->fcmService->storeNotification($user, $title, $body, $data);

            Mail::to($user->email)->send(new VolunteerApplicationDecision($status, $projectName));
        } catch (Throwable $e) {
            Log::error('Failed to notify volunteer of application decision', [
                'participant_id' => $participant->id,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }
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

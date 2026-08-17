<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Complain;
use App\Models\Notification;
use App\Models\Profile;
use App\Models\ScoreLog;
use App\Repositories\AdminComplainRepository;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminComplainService
{
    private AdminComplainRepository $repository;
    private CitizenshipService $citizenshipService; 

    public function __construct(AdminComplainRepository $repository, CitizenshipService $citizenshipService)
    {
        $this->repository = $repository;
        $this->citizenshipService = $citizenshipService;
    }

    public function list(array $filters): array
    {
        $result = $this->repository->getComplains(
            $filters,
            $filters['page'] ?? 1,
            $filters['page_size'] ?? 15
        );

        return [
            'data' => $result,
            'message' => 'Complaints retrieved successfully',
            'code' => 200,
        ];
    }

    public function details(int $id): array
    {
        $complain = $this->repository->find($id);

        if (!$complain) {
            throw new NotFoundHttpException('Complaint not found');
        }

        return [
            'data' => $complain,
            'message' => 'Complaint details retrieved successfully',
            'code' => 200,
        ];
    }

    public function review(int $id, array $validated, int $adminId): array
    {
        $complain = $this->repository->find($id);

        if (!$complain) {
            throw new NotFoundHttpException('Complaint not found');
        }

        if ($complain->status !== 'under_review') {
            throw new ConflictHttpException('Only complaints under review can be reviewed');
        }

        $approve = $validated['action'] === 'approve';
        $weight = (float) ($complain->category->weight ?? 0);

        DB::transaction(function () use ($complain, $approve, $validated, $adminId, $weight) {
            // 1. تحديث حالة الشكوى
            $this->repository->update($complain->id, [
                'status' => $approve ? 'published' : 'rejected',
                'decision_reason' => $validated['decision_reason'] ?? null,
            ]);

            // 2. تحديث مؤشر المواطنة (استخدام الخدمة كما هي - بدون تعديل عليها)
            if ($weight > 0 && $complain->user) {
                if ($approve) {
                    $this->citizenshipService->increase($complain->user, $weight);
                } else {
                    $this->citizenshipService->decrease($complain->user, $weight);
                }
            }

            // 3. تسجيل القرار في AuditLog
            AuditLog::create([
                'user_id' => $adminId,
                'auditable_type' => Complain::class,
                'auditable_id' => $complain->id,
                'action' => $approve ? 'approve' : 'reject',
            ]);

            // 4. إشعار مقدم الشكوى
            Notification::create([
                'user_id' => $complain->user_id,
                'title' => $approve ? 'Complaint Approved' : 'Complaint Rejected',
                'body' => $approve
                    ? "Your complaint \"{$complain->title}\" has been published."
                    : "Your complaint \"{$complain->title}\" was rejected. Reason: {$validated['decision_reason']}",
            ]);
        });

        return [
            'data' => $this->repository->find($id),
            'message' => $approve ? 'Complaint approved and published' : 'Complaint rejected',
            'code' => 200,
        ];
    }

    public function updateStatus(int $id, array $validated, int $adminId): array
    {
        $complain = $this->repository->find($id);

        if (!$complain) {
            throw new NotFoundHttpException('Complaint not found');
        }

        $newStatus = $validated['status'];

        $allowedFrom = [
            'in_progress' => 'published',
            'closed' => 'in_progress',
        ];

        if ($complain->status !== $allowedFrom[$newStatus]) {
            throw new ConflictHttpException("Cannot change status from {$complain->status} to {$newStatus}");
        }

        DB::transaction(function () use ($complain, $newStatus, $adminId) {
            $this->repository->update($complain->id, ['status' => $newStatus]);

            if ($newStatus === 'closed') {
                AuditLog::create([
                    'user_id' => $adminId,
                    'auditable_type' => Complain::class,
                    'auditable_id' => $complain->id,
                    'action' => 'close',
                ]);

                Notification::create([
                    'user_id' => $complain->user_id,
                    'title' => 'Complaint Closed',
                    'body' => "Your complaint \"{$complain->title}\" has been resolved and closed.",
                ]);
            }
        });

        return [
            'data' => $this->repository->find($id),
            'message' => "Complaint status changed to {$newStatus}",
            'code' => 200,
        ];
    }
}
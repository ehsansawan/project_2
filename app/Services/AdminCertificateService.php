<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\CertificateStatus;
use App\Models\User;
use App\Models\UserCertificate;
use Illuminate\Support\Facades\DB;

class AdminCertificateService
{
    /**
     * Create a new class instance.
     */
    private CredibilityService $credibilityService;
    private FcmService $fcmService;
    public function __construct(
        CredibilityService $credibilityService,
        FcmService $fcmService,
    )
    {
        //
        $this->credibilityService = $credibilityService;
        $this->fcmService = $fcmService;
    }
    public function getCertificates($request): array
    {
        $query = UserCertificate::query()
            ->with('userSkill.user.profile');   // أو 'user' لو أصلحت العلاقة'

        if (!empty($request['status']))
        {
             $query->whereIn('status', $request['status']);
        }

        $certificates = $query->latest()->paginate(15);

        return [
            'data' => $certificates,
            'message' => 'Certificates retrieved successfully.',
            'code' => 200,
        ];
    }
    public function getCertificate(int $id): array
    {
        $certificate = UserCertificate::query()
            ->with('userSkill.user.profile')
            ->find($id);

        if (!$certificate) {
            return ['data' => null, 'message' => 'Certificate not found.', 'code' => 404];
        }

        return [
            'data' => $certificate,
            'message' => 'Certificate retrieved successfully.',
            'code' => 200,
        ];
    }
    public function getUserCertificates(int $userId): array
    {
        $user = User::query()->find($userId);

        if (!$user) {
            return ['data' => null, 'message' => 'User not found.', 'code' => 404];
        }

        $certificates = UserCertificate::query()
            ->with(['userSkill.user.profile'])
            ->whereHas('userSkill', fn ($q) => $q->where('user_id', $userId))
            ->latest()
            ->paginate(15);

        return [
            'data' => $certificates,
            'message' => 'User certificates retrieved successfully.',
            'code' => 200,
        ];
    }
    public function approve(int $id): array
    {
        $certificate = UserCertificate::query()
            ->with('userSkill.user.profile')
            ->find($id);

        if (!$certificate) {
            return ['data' => null, 'message' => 'Certificate not found.', 'code' => 404];
        }

        if ($certificate->status !== CertificateStatus::Pending->value) {
            return ['data' => null, 'message' => 'Only pending certificates can be approved.', 'code' => 409];
        }

        DB::beginTransaction();

        try {
            $certificate->update([
                'status' => CertificateStatus::Approved->value,
            ]);

            $user = $certificate->userSkill->user;

            // زيادة المصداقية (نسبة من المسافة لـ 100)
            $this->credibilityService->increase($user, 0.05);

            // سجل التدقيق
            $certificate->audits()->create([
                'user_id' => auth()->id(),
                'action' => AuditAction::Approve->value,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ['data' => null, 'message' => $e->getMessage(), 'code' => 500];
        }

        // إشعار (بعد الـ commit)
        $this->fcmService->sendToUser(
            $certificate->userSkill->user,
            'Certificate Approved',
            'Your certificate has been approved.',
            ['type' => 'certificate', 'status' => 'approved', 'certificate_id' => (string) $certificate->id]
        );
        //store notification
        $this->fcmService->storeNotification(
            $certificate->userSkill->user,
            'Certificate Approved',
            'Your certificate has been approved.',
            ['type' => 'certificate', 'status' => 'approved', 'certificate_id' => (string) $certificate->id]
        );

        return [
            'data' => $certificate->fresh()->load('userSkill.user.profile'),
            'message' => 'Certificate approved successfully.',
            'code' => 200,
        ];
    }
    public function reject(int $id, array $request): array
    {
        $certificate = UserCertificate::query()
            ->with('userSkill.user.profile')
            ->find($id);

        if (!$certificate) {
            return ['data' => null, 'message' => 'Certificate not found.', 'code' => 404];
        }

        if ($certificate->status !== CertificateStatus::Pending->value) {
            return ['data' => null, 'message' => 'Only pending certificates can be rejected.', 'code' => 409];
        }

        DB::beginTransaction();

        try {
            $certificate->update([
                'status' => CertificateStatus::Rejected->value,
                'rejection_reason' => $request['rejection_reason'],
            ]);

            $user = $certificate->userSkill->user;

            // نقصان المصداقية (نسبة من القيمة الحالية)
            $this->credibilityService->decrease($user, 0.10);

            // سجل التدقيق
            $certificate->audits()->create([
                'user_id' => auth()->id(),
                'action' => AuditAction::Reject->value,
                'reason' => $request['rejection_reason'],
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return ['data' => null, 'message' => $e->getMessage(), 'code' => 500];
        }

        // إشعار
        $this->fcmService->sendToUser(
            $certificate->userSkill->user,
            'Certificate Rejected',
            'Your certificate was rejected. Reason: ' . $request['rejection_reason'],
            ['type' => 'certificate', 'status' => 'rejected', 'certificate_id' => (string) $certificate->id]
        );
        // + storeNotification
        $this->fcmService->storeNotification(
            $certificate->userSkill->user,
            'Certificate Rejected',
            'Your certificate was rejected. Reason: ' . $request['rejection_reason'],
            ['type' => 'certificate', 'status' => 'rejected', 'certificate_id' => (string) $certificate->id]
        );

        return [
            'data' => $certificate->fresh()->load('userSkill.user.profile'),
            'message' => 'Certificate rejected successfully.',
            'code' => 200,
        ];
    }
}
